#!/bin/bash
#
# autocomplete-make.sh
#
# generate bash autocomplete file for magerun
#
set -euo pipefail
IFS=$'\n\t'

base=magerun
name=n98-${base}
bash_target=autocompletion/bash/bash_complete

if [ ! -d "build" ]; then
	echo >2 "error: could not find 'build' directory"
	exit 1
fi

if [ ! -e "bin/${name}" ]; then
	echo >2 "error: could not find 'bin/${name}' script"
	exit 2
fi


cd build

makedir=autocomplete

header()
{
    cat <<EOF
#!/bin/bash
# Installation:
#  Copy to /etc/bash_completion.d/n98-magerun.phar
# or
#  Append to ~/.bash_completion
# open new or restart existing shell session

EOF
}

if [ ! -d "${makedir}" ]; then
	mkdir "${makedir}"
fi
(
	echo "setting up build requirements in '${makedir}'"
	composer -d="${makedir}" require bamarni/symfony-console-autocomplete

	cd "${makedir}"

	echo "creating autocomplete file..."

	php -f vendor/bin/symfony-autocomplete -- -- "../../bin/${name}" > bash-autocomplete.sh

    outfile="../../${bash_target}"
    header > "${outfile}"

    # remove first line and expand last line to more command names (aliases)
    sed '1d ; $ s/$/.phar '"${name} ${base}"'/' bash-autocomplete.sh >> "${outfile}"
    rm bash-autocomplete.sh

	echo "updated ${bash_target}."
)

rm -r "${makedir}"
