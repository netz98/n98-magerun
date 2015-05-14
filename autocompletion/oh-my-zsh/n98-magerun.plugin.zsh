# Installation:
#  Copy to ~/.oh-my-zsh/custom/plugins/n98-magerun/n98-magerun.plugin.zsh
#  Edit plugins in your .zshrc e.g. "plugins=(git n98-magerun)"
#  It will load the next time a shell session is started
#  This plugin assumes that "n98-magerun.phar" is in your path

_n98_magerun_get_command_list () {
  n98-magerun.phar --raw --no-ansi list | sed "s/\s.*//g"
}

_n98_magerun () {
  compadd `_n98_magerun_get_command_list`
}

compdef _n98_magerun n98-magerun.phar
