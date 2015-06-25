#compdef n98-magerun.phar

# Installation:
#  This assumes that "n98-magerun.phar" is in your path
#
#  Place this file in a directory listed in the $fpath variable.
#  You can add a directory to $fpath by adding a line like this to your
#  ~/.zshrc file:
#
#      fpath=(~/.zsh_completion.d $fpath)
#
# Oh My Zsh Installation:
#  Copy to ~/.oh-my-zsh/custom/plugins/n98-magerun/n98-magerun.plugin.zsh
#  Edit plugins in your .zshrc e.g. "plugins=(git n98-magerun)"
#
#  It will load the next time a shell session is started

_n98_magerun_get_command_list () {
  n98-magerun.phar --raw --no-ansi list | sed "s/[[:space:]].*//g"
}

_n98_magerun () {
  compadd `_n98_magerun_get_command_list`
}

compdef _n98_magerun n98-magerun.phar
