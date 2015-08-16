#!/bin/bash
{
# build version tasks in magerung project
#
# Copyright (C) 2015 Tom Klingenberg <mot@fsfe.org>
#
# This file defines actions to handle version bumps in magerun. It orchestrates the files version.txt changes.txt and
# Application.php and refuses operation on inconsistent settings.
#

set -euo pipefail
IFS=$'\n\t'

FILE_VERSION="version.txt"
FILE_CHANGES="changes.txt"
FILE_APPLICATION="src/N98/Magento/Application.php"

FLAG_GITGUI=1

print_usage() {
    echo "usage: build/bin/version.sh [<to>] [<from>]"
    echo "   or: build/bin/version.sh --current"
    echo "   or: build/bin/version.sh --bump[=<context>] [<from>]"
    echo "   or: build/bin/version.sh --tag"
    echo ""
    echo "    --current          show current version"
    echo "    --bump[=<context>] give bumped up version, default <context> "
    echo "                       \"patch\", others \"minor\", \"major\""
    echo "    --tag              tag a release"
    echo ""
    echo "Version parameters"
    echo "    <to>, <from>       a version number, both optional."
    echo ""
    echo "Global switches"
    echo "    --no-git-gui       do not use git gui"
    echo ""
}

validate_version() {
    local ver="${1}"

    if echo "${ver}" | grep -q -e '^[1-9][0-9]\{0,4\}\.[1-9][0-9]\{0,4\}\.[1-9][0-9]\{0,4\}$'; then
        return 0
    else
        return 1
    fi
}

current_version() {
    VERSION_CURRENT="$(head -n 1 ${FILE_VERSION})"
    if [ $? -ne 0 ]; then
        >&2 echo "fatal: unable to obtain current version"
        exit 128
    fi

    if ! validate_version "${VERSION_CURRENT}"; then
        echo "fatal: the version system has to be fixed, invalid version \"${VERSION_CURRENT}\" in ${FILE_VERSION}"
        exit 128
    fi
}

bump_version() {
    local scope="${1}"
    local version="${2-}"

    if [ -z "${version}" ]; then
        current_version
        version="${VERSION_CURRENT}"
    elif ! validate_version "${version}"; then
        >&2 echo "not a version: \"${version}\""
        exit 2
    fi

    local list=(`echo "${version}" | tr '.' '\n'`)

    local v_major=${list[0]}
    local v_minor=${list[1]}
    local v_patch=${list[2]}

    case ${scope} in
        major)
            v_major=$((v_major + 1))
            v_minor="0"
            v_patch="0"
        ;;
        minor)
            v_minor=$((v_minor + 1))
            v_patch="0"
        ;;
        patch)
            v_patch=$((v_patch + 1))
        ;;
        *)
            # unknown scope
            >&2 echo "unknown bump scope: \"${scope}\""
            exit 2
        ;;
    esac

    local bumped="${v_major}.${v_minor}.${v_patch}"

    if ! validate_version "${bumped}"; then
        >&2 echo "error: can not bump ${scope} in \"${version}\""
        exit 2
    fi

    echo "${bumped}"

    exit 0
}

# TODO: - tag a release
#         tag for current version: $ git tag -a `cat version.txt` -m "tag version `cat version.txt`"
#         show last X latest tags: $ git tag | sort -rn | head -2 | xargs git show
tag_release()
{
    echo "tag a release"

    # checkout develop
    # update to latest (fetch && merge --ff-only)
    # obtain version
    # check if a tag with the version already exists
    # create release branch if not yet exists
    # checkout master
    # merge the release branch
    # delete the release branch
    # tag

    if ! git checkout -q develop; then
        >&2 echo "failed to checkout develop branch"
        exit 2
    fi

    echo "checked out develop."

    if ! git fetch; then
        >&2 echo "failed to fetch (from within develop branch)"
        exit 2
    fi

    local tracking=$(git for-each-ref --format='%(upstream:short)' $(git symbolic-ref -q HEAD))
    if [ $? -ne 0 ]; then
        >&2 echo "failed to obtain tracking branch (of develop branch)"
        exit 2
    fi

    if ! git merge --ff-only "${tracking}"; then
        >&2 echo "failed to merge tracking branch '${tracking}' (from within develop branch)"
        exit 2
    fi

    echo "up-to-date with tracking branch ${tracking}."

    current_version
    VERSION_RELEASE="${VERSION_CURRENT}"
    echo "release version: ${VERSION_RELEASE}"

    if git show-ref -q --tags --verify "refs/tags/${VERSION_RELEASE}"; then
        >&2 echo "tag for release ${VERSION_RELEASE} exists already"
        exit 2
    fi

    RELEASE_BRANCH="release/${VERSION_RELEASE}"
    if ! git checkout -b "${RELEASE_BRANCH}"; then
        >&2 echo "failed to checkout release branch ${RELEASE_BRANCH}"
        exit 2
    fi

    if ! git checkout -q master; then
        >&2 echo "failed to checkout master branch"
        exit 2
    fi

    echo -n "fetching from remote: "
    echo -n "branches... "
    if ! git fetch; then
        >&2 echo "failed, aborting"
        exit 2
    fi
    echo -n "tags... "
    if ! git fetch --tags; then
        >&2 echo "failed, aborting"
        exit 2
    fi

    LOCAL=$(git rev-parse @)
    REMOTE=$(git rev-parse @{u})
    BASE=$(git merge-base @ @{u})

    if [ $LOCAL = $REMOTE ]; then
        echo "- up-to-date"
    elif [ $LOCAL = $BASE ]; then
        echo "- need to pull"
        exit 2
    elif [ $REMOTE = $BASE ]; then
        echo "- need to push"
        exit 2
    else
        echo "- diverged"
        exit 2
    fi

    if ! git merge --no-ff "${RELEASE_BRANCH}"; then
        >&2 echo "failed to merge release branch ${RELEASE_BRANCH}"
        exit 2
    fi

    if ! git branch -d "${RELEASE_BRANCH}"; then
        >&2 echo "failed to remove release branch ${RELEASE_BRANCH}"
        exit 2
    fi

    if ! git tag -a "${VERSION_RELEASE}" -m "Tagged version ${VERSION_RELEASE}"; then
        >&2 echo "failed to create tag"
        exit 2
    fi

    exit 0
}


#####################################################################

# remove: create a <code>eval set -- $(remove #1 #2 #3)</code> operation to remove one or more positional parameters at any
#         place in the sequence
#
# parameters:
#
#   1: total number of positional parameters (defaults to 0)
#   2: position from which to remove (defaults to 2)
#   3: number of parameters to remove (defaults to 1)
#
remove() {

    # number of parameters is smaller 1 - nothing to remove
    if [ "${1:-0}" -lt "1" ]; then
        return
    fi

    # changes would be beyond parameters - unchanged
    if [ "${2:-2}" -gt "${1}" ]; then
        echo '"${@}"'
        return
    fi

    # number of changes is smaller 1 - unchanged
    if [ "${3:-1}" -le "0" ]; then
        echo '"${@}"'
        return
    fi

    local buffer=""
    local start_len=$((${2:-2} - 1))
    local end_at=$((${2:-2} + ${3:-1}))
    local end_len=$((${1} - end_at + 1))

    # check if there is a segment starting at position 1
    if [ "${start_len}" -gt "0" ]; then
        buffer='"${@:1:'"${start_len}"'}"'
    fi

    # check if there is a segment after those that were removed
    if [ "${end_len}" -gt "0" ]; then
        if [ ! -z "${buffer}" ]; then
            buffer+=" "
        fi
        buffer+='"${@:'"${end_at}"':'"${end_len}"'}"'
    fi

    echo "${buffer}"
}

# global switches

c=1
for i in "$@"
do
case ${i} in
    --no-git-gui)
        eval set -- $(remove $# "${c}")
        FLAG_GITGUI=2
    ;;
    -h|--help)
        print_usage
        exit 0
    ;;
    -*)
        # unknown option
        ((c = c + 1))
    ;;
    --|*)
        # separator or first non-switch
        break;
    ;;
esac
done

operational_switches() {
    while [ "$#" -gt 0 ]; do
      case "$1" in
        --current)
            current_version
            echo "${VERSION_CURRENT}"
            exit 0
        ;;
        --bump)
            bump_version "patch" "${2:-}"
        ;;
        --bump=*)
            bump_version "${1#*=}" "${2:-}"
        ;;
        --tag)
            tag_release
        ;;
        --*)
            >&2 echo "unkown switch ${1}"
            print_usage
            exit 1
        ;;
        *)
            # unknown option
            shift
        ;;
        esac
    done
}

operational_switches $@

current_version
echo "version file is ${VERSION_CURRENT}"

# no version parameter given
if [ -z "${1:-}" ]; then
    VERSION_TO="$(bump_version patch ${VERSION_CURRENT})"
else
    VERSION_TO="${1}"
fi

VERSION_FROM="${2:-}"

if ! validate_version "${VERSION_TO}"; then
    echo "error: to \"${VERSION_TO}\" invalid"
    exit 1
fi

if [ -z "${VERSION_FROM}" ]; then
    VERSION_FROM="${VERSION_CURRENT}"
elif ! validate_version "${VERSION_FROM}"; then
    echo "error: to \"${VERSION_FROM}\" invalid"
    exit 1
fi

echo "updating from ${VERSION_FROM} to ${VERSION_TO}";

#####################################################################

# 1. check - can version.txt be correctly updated?

if ! grep -qF "${VERSION_FROM}" version.txt ; then
    echo "error: can't update version.txt from \"${VERSION_FROM}\""
    exit 1
fi

# 2. check - can changes.txt be updated?

line="================"

CHANGES_HEAD="**************
RECENT CHANGES
**************

${line:0:${#VERSION_FROM}}
${VERSION_FROM}
${line:0:${#VERSION_FROM}}"
CHANGES_NEW="**************
RECENT CHANGES
**************

${line:0:${#VERSION_TO}}
${VERSION_TO}
${line:0:${#VERSION_TO}}"

if [ "${CHANGES_HEAD}" != "$(head -n 8 ${FILE_CHANGES})" ]; then
    echo "error: can't update changes file from \"${VERSION_FROM}\""
    exit 1
fi

# 3. check - can src/N98/Magento/Application.php be correctly updated?

APPLICATION_LINE="    const APP_VERSION = '${VERSION_FROM}';"
APPLICATION_NEW="    const APP_VERSION = '${VERSION_TO}';"

count=$(grep -cxF "${APPLICATION_LINE}" "${FILE_APPLICATION}");
if [ $? -ne 0 ] ; then
    echo "fatal: can't update application file from \"${VERSION_FROM}\""
    exit 128
fi

if [ "${count}" != "1" ] ; then
    echo "error: can't update application file from \"${VERSION_FROM}\""
    exit 128
fi

#####################################################################

# 4. update application file

if sed -i.~old "s~^${APPLICATION_LINE}\$~${APPLICATION_NEW}~" "${FILE_APPLICATION}"; then
    rm "${FILE_APPLICATION}".~old
fi

# 5. uupdate cahnges file

echo "${CHANGES_NEW}" > "${FILE_CHANGES}".~new
tail -n+4 "${FILE_CHANGES}" >> "${FILE_CHANGES}".~new
mv "${FILE_CHANGES}".~new "${FILE_CHANGES}"

# 6. update version file

echo "${VERSION_TO}" > "${FILE_VERSION}"

#####################################################################

git add "${FILE_VERSION}"
git add "${FILE_CHANGES}"
git add "${FILE_APPLICATION}"

if [ ${FLAG_GITGUI} -eq 1 ]; then
    echo "[TASK] update version of development branch" > .git/GITGUI_MSG
    git gui
fi

exit
}
