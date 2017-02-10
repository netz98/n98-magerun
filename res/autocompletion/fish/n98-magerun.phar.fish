# Installation:
#  This assumes that "n98-magerun.phar" is in your path!
#  Copy to ~/.config/fish/completions/n98-magerun.phar.fish
# open new or restart existing shell session

for cmd in (n98-magerun.phar --raw --no-ansi list | sed "s/[[:space:]].*//g");
    complete -f -c n98-magerun.phar -a $cmd;
end

