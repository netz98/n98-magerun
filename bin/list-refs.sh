#!/bin/bash
#
# prepare a list of refs for a git commit message based on file to be committed
# sources information from files staged in the commit and the current GITGUI
# commit / backup message
#
set -euo pipefail
IFS=$'\n\t'

PROJECT_DIR="$(git rev-parse --show-toplevel)"

guess_gitfile() {
  GITFILE=GITGUI_MSG
  if [[ ! -e "${PROJECT_DIR}/.git//${GITFILE}" ]]; then
    GITFILE=GITGUI_BCK
  fi
}

list_refs() {
  local file="${PROJECT_DIR}"/.git/${GITFILE}
  if [ ! -e "${file}" ]; then
    return 0;
  fi
  local shorts="$(grep -o "\b[0-9a-f]\{7,7\}\b" ${file})"
  for short in $shorts; do
    echo "- $(git log --oneline $short -1)"
  done;
}

list_issues() {
  local file="${PROJECT_DIR}"/.git/${GITFILE}
  if [ ! -e "${file}" ]; then
    return 0;
  fi
  local issues="$(sed -n 's/^.*\(#[0-9]\+\).*$/\1/p' "${file}" | sort -rn | uniq)"
  for issue in $issues; do
    echo "- $issue"
  done;
}

list_commands() {
  for file in $(git diff --name-only --cached | grep Command.php$); do
    sed -n 's/^.* ->setName(["'\'']\([^"'\'']*\).*)$/- Command: \1/p' "$file"
  done;
}

guess_gitfile

echo "Refs:"
(
  list_refs
  list_issues
  list_commands
) | sed 's/^/&\n/'

echo ""
