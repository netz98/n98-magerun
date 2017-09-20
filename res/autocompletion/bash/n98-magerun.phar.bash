#!/bin/bash
# Installation:
#  Copy to /etc/bash_completion.d/n98-magerun.phar
# or
#  Append to ~/.bash_completion
# open new or restart existing shell session


_n98-magerun()
{
    local cur script coms opts com
    COMPREPLY=()
    _get_comp_words_by_ref -n : cur words

    # for an alias, get the real script behind it
    if [[ $(type -t ${words[0]}) == "alias" ]]; then
        script=$(alias ${words[0]} | sed -E "s/alias ${words[0]}='(.*)'/\1/")
    else
        script=${words[0]}
    fi

    # lookup for command
    for word in ${words[@]:1}; do
        if [[ $word != -* ]]; then
            com=$word
            break
        fi
    done

    # completing for an option
    if [[ ${cur} == --* ]] ; then
        opts="--help --quiet --verbose --version --ansi --no-ansi --no-interaction --root-dir --skip-config --skip-root-check --developer-mode"

        case "$com" in
            help)
            opts="${opts} --xml --format --raw"
            ;;
            install)
            opts="${opts} --magentoVersion --magentoVersionByName --installationFolder --dbHost --dbUser --dbPass --dbName --dbPort --dbPrefix --installSampleData --useDefaultConfigParams --baseUrl --replaceHtaccessFile --noDownload --only-download --forceUseDb"
            ;;
            list)
            opts="${opts} --xml --raw --format"
            ;;
            open-browser)
            opts="${opts} "
            ;;
            script)
            opts="${opts} --define --stop-on-error"
            ;;
            shell)
            opts="${opts} "
            ;;
            uninstall)
            opts="${opts} --force --installationFolder"
            ;;
            admin:notifications)
            opts="${opts} --on --off"
            ;;
            admin:user:change-password)
            opts="${opts} "
            ;;
            admin:user:change-status)
            opts="${opts} --activate --deactivate"
            ;;
            admin:user:create)
            opts="${opts} "
            ;;
            admin:user:delete)
            opts="${opts} --force"
            ;;
            admin:user:list)
            opts="${opts} --format"
            ;;
            cache:clean)
            opts="${opts} --reinit --no-reinit"
            ;;
            cache:dir:flush)
            opts="${opts} "
            ;;
            cache:disable)
            opts="${opts} "
            ;;
            cache:enable)
            opts="${opts} "
            ;;
            cache:flush)
            opts="${opts} --reinit --no-reinit"
            ;;
            cache:list)
            opts="${opts} --format"
            ;;
            cache:report)
            opts="${opts} --tags --mtime --filter-id --filter-tag --fpc --format"
            ;;
            cache:view)
            opts="${opts} --unserialize --fpc"
            ;;
            category:create:dummy)
            opts="${opts} "
            ;;
            cms:block:toggle)
            opts="${opts} "
            ;;
            composer:diagnose)
            opts="${opts} "
            ;;
            composer:init)
            opts="${opts} --name --description --author --type --homepage --require --require-dev --stability --license --repository"
            ;;
            composer:install)
            opts="${opts} --prefer-source --prefer-dist --dry-run --dev --no-dev --no-custom-installers --no-autoloader --no-scripts --no-progress --no-suggest --optimize-autoloader --classmap-authoritative --apcu-autoloader --ignore-platform-reqs"
            ;;
            composer:require)
            opts="${opts} --dev --prefer-source --prefer-dist --no-progress --no-suggest --no-update --no-scripts --update-no-dev --update-with-dependencies --ignore-platform-reqs --prefer-stable --prefer-lowest --sort-packages --optimize-autoloader --classmap-authoritative --apcu-autoloader"
            ;;
            composer:search)
            opts="${opts} --only-name --type"
            ;;
            composer:update)
            opts="${opts} --prefer-source --prefer-dist --dry-run --dev --no-dev --lock --no-custom-installers --no-autoloader --no-scripts --no-progress --no-suggest --with-dependencies --optimize-autoloader --classmap-authoritative --apcu-autoloader --ignore-platform-reqs --prefer-stable --prefer-lowest --interactive --root-reqs"
            ;;
            composer:validate)
            opts="${opts} --no-check-all --no-check-lock --no-check-publish --with-dependencies --strict"
            ;;
            config:delete)
            opts="${opts} --scope --scope-id --force --all"
            ;;
            config:dump)
            opts="${opts} "
            ;;
            config:get)
            opts="${opts} --scope --scope-id --decrypt --update-script --magerun-script --format"
            ;;
            config:search)
            opts="${opts} "
            ;;
            config:set)
            opts="${opts} --scope --scope-id --encrypt --force --no-null"
            ;;
            customer:change-password)
            opts="${opts} "
            ;;
            customer:create)
            opts="${opts} --format"
            ;;
            customer:create:dummy)
            opts="${opts} --with-addresses --format"
            ;;
            customer:delete)
            opts="${opts} --all --force --range"
            ;;
            customer:info)
            opts="${opts} "
            ;;
            customer:list)
            opts="${opts} --format"
            ;;
            db:console)
            opts="${opts} --use-mycli-instead-of-mysql --no-auto-rehash"
            ;;
            db:create)
            opts="${opts} "
            ;;
            db:drop)
            opts="${opts} --tables --force"
            ;;
            db:dump)
            opts="${opts} --add-time --compression --dump-option --xml --hex-blob --only-command --print-only-filename --dry-run --no-single-transaction --human-readable --add-routines --stdout --strip --exclude --include --force"
            ;;
            db:import)
            opts="${opts} --compression --only-command --only-if-empty --optimize --drop --drop-tables"
            ;;
            db:info)
            opts="${opts} --format"
            ;;
            db:maintain:check-tables)
            opts="${opts} --type --repair --table --format"
            ;;
            db:query)
            opts="${opts} --only-command"
            ;;
            db:status)
            opts="${opts} --format --rounding --no-description"
            ;;
            db:variables)
            opts="${opts} --format --rounding --no-description"
            ;;
            design:demo-notice)
            opts="${opts} --on --off --global"
            ;;
            dev:class:lookup)
            opts="${opts} "
            ;;
            dev:code:model:method)
            opts="${opts} "
            ;;
            dev:console)
            opts="${opts} "
            ;;
            dev:email-template:usage)
            opts="${opts} --format"
            ;;
            dev:ide:phpstorm:meta)
            opts="${opts} --stdout"
            ;;
            dev:log)
            opts="${opts} --on --off --global"
            ;;
            dev:log:db)
            opts="${opts} --on --off"
            ;;
            dev:log:size)
            opts="${opts} --human"
            ;;
            dev:merge-css)
            opts="${opts} --on --off --global"
            ;;
            dev:merge-js)
            opts="${opts} --on --off --global"
            ;;
            dev:module:create)
            opts="${opts} --add-controllers --add-blocks --add-helpers --add-models --add-setup --add-all --modman --add-readme --add-composer --author-name --author-email --description"
            ;;
            dev:module:dependencies:from)
            opts="${opts} --all --format"
            ;;
            dev:module:dependencies:on)
            opts="${opts} --all --format"
            ;;
            dev:module:disable)
            opts="${opts} --codepool"
            ;;
            dev:module:enable)
            opts="${opts} --codepool"
            ;;
            dev:module:list)
            opts="${opts} --codepool --status --vendor --format"
            ;;
            dev:module:observer:list)
            opts="${opts} --format --sort"
            ;;
            dev:module:rewrite:conflicts)
            opts="${opts} --log-junit"
            ;;
            dev:module:rewrite:list)
            opts="${opts} --format"
            ;;
            dev:module:update)
            opts="${opts} --set-version --add-blocks --add-helpers --add-models --add-all --add-resource-model --add-routers --add-events --add-layout-updates --add-translate --add-default"
            ;;
            dev:profiler)
            opts="${opts} --on --off --global"
            ;;
            dev:report:count)
            opts="${opts} "
            ;;
            dev:setup:script:attribute)
            opts="${opts} "
            ;;
            dev:symlinks)
            opts="${opts} --on --off --global"
            ;;
            dev:template-hints)
            opts="${opts} --on --off"
            ;;
            dev:template-hints-blocks)
            opts="${opts} --on --off"
            ;;
            dev:theme:duplicates)
            opts="${opts} --log-junit"
            ;;
            dev:theme:info)
            opts="${opts} "
            ;;
            dev:theme:list)
            opts="${opts} --format"
            ;;
            dev:translate:admin)
            opts="${opts} --on --off"
            ;;
            dev:translate:export)
            opts="${opts} --store"
            ;;
            dev:translate:set)
            opts="${opts} "
            ;;
            dev:translate:shop)
            opts="${opts} --on --off"
            ;;
            eav:attribute:create-dummy-values)
            opts="${opts} "
            ;;
            eav:attribute:list)
            opts="${opts} --filter-type --add-source --add-backend --format"
            ;;
            eav:attribute:remove)
            opts="${opts} "
            ;;
            eav:attribute:view)
            opts="${opts} --format"
            ;;
            extension:download)
            opts="${opts} "
            ;;
            extension:install)
            opts="${opts} "
            ;;
            extension:list)
            opts="${opts} --format"
            ;;
            extension:upgrade)
            opts="${opts} "
            ;;
            extension:validate)
            opts="${opts} --skip-file --skip-hash --full-report --include-default"
            ;;
            index:list)
            opts="${opts} --format"
            ;;
            index:list:mview)
            opts="${opts} --format"
            ;;
            index:reindex)
            opts="${opts} "
            ;;
            index:reindex:all)
            opts="${opts} "
            ;;
            index:reindex:mview)
            opts="${opts} "
            ;;
            local-config:generate)
            opts="${opts} "
            ;;
            media:cache:image:clear)
            opts="${opts} "
            ;;
            media:cache:jscss:clear)
            opts="${opts} "
            ;;
            media:dump)
            opts="${opts} --strip"
            ;;
            script:repo:list)
            opts="${opts} --format"
            ;;
            script:repo:run)
            opts="${opts} --define --stop-on-error"
            ;;
            sys:check)
            opts="${opts} --format"
            ;;
            sys:cron:history)
            opts="${opts} --timezone --format"
            ;;
            sys:cron:list)
            opts="${opts} --format"
            ;;
            sys:cron:run)
            opts="${opts} "
            ;;
            sys:info)
            opts="${opts} --format"
            ;;
            sys:maintenance)
            opts="${opts} --on --off"
            ;;
            sys:setup:change-version)
            opts="${opts} "
            ;;
            sys:setup:compare-versions)
            opts="${opts} --ignore-data --log-junit --errors-only --format"
            ;;
            sys:setup:incremental)
            opts="${opts} --stop-on-error"
            ;;
            sys:setup:remove)
            opts="${opts} "
            ;;
            sys:setup:run)
            opts="${opts} --no-implicit-cache-flush"
            ;;
            sys:store:config:base-url:list)
            opts="${opts} --format"
            ;;
            sys:store:list)
            opts="${opts} --format"
            ;;
            sys:url:list)
            opts="${opts} --add-categories --add-products --add-cmspages --add-all"
            ;;
            sys:website:list)
            opts="${opts} --format"
            ;;

        esac

        COMPREPLY=($(compgen -W "${opts}" -- ${cur}))
        __ltrim_colon_completions "$cur"

        return 0;
    fi

    # completing for a command
    if [[ $cur == $com ]]; then
        coms="help install list open-browser script shell uninstall admin:notifications admin:user:change-password admin:user:change-status admin:user:create admin:user:delete admin:user:list cache:clean cache:dir:flush cache:disable cache:enable cache:flush cache:list cache:report cache:view category:create:dummy cms:block:toggle composer:diagnose composer:init composer:install composer:require composer:search composer:update composer:validate config:delete config:dump config:get config:search config:set customer:change-password customer:create customer:create:dummy customer:delete customer:info customer:list db:console db:create db:drop db:dump db:import db:info db:maintain:check-tables db:query db:status db:variables design:demo-notice dev:class:lookup dev:code:model:method dev:console dev:email-template:usage dev:ide:phpstorm:meta dev:log dev:log:db dev:log:size dev:merge-css dev:merge-js dev:module:create dev:module:dependencies:from dev:module:dependencies:on dev:module:disable dev:module:enable dev:module:list dev:module:observer:list dev:module:rewrite:conflicts dev:module:rewrite:list dev:module:update dev:profiler dev:report:count dev:setup:script:attribute dev:symlinks dev:template-hints dev:template-hints-blocks dev:theme:duplicates dev:theme:info dev:theme:list dev:translate:admin dev:translate:export dev:translate:set dev:translate:shop eav:attribute:create-dummy-values eav:attribute:list eav:attribute:remove eav:attribute:view extension:download extension:install extension:list extension:upgrade extension:validate index:list index:list:mview index:reindex index:reindex:all index:reindex:mview local-config:generate media:cache:image:clear media:cache:jscss:clear media:dump script:repo:list script:repo:run sys:check sys:cron:history sys:cron:list sys:cron:run sys:info sys:maintenance sys:setup:change-version sys:setup:compare-versions sys:setup:incremental sys:setup:remove sys:setup:run sys:store:config:base-url:list sys:store:list sys:url:list sys:website:list"

        COMPREPLY=($(compgen -W "${coms}" -- ${cur}))
        __ltrim_colon_completions "$cur"

        return 0
    fi
}

complete -o default -F _n98-magerun n98-magerun.phar n98-magerun magerun
