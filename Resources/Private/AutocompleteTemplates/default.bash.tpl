#!/bin/bash

_typo3console()
{
    local cur script com opts
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
        opts=$script
        [[ -n $com ]] && opts=$opts" help "$com
        opts=$($opts 2>/dev/null | sed -n '/Options/,/^$/p' | sed -e '1d;$d' | sed 's/[^--]*\(--.*\)/\1/' | sed -En 's/[^ ]*(-(-[[:alnum:]]+){1,}).*/\1/p' | awk '{$1=$1};1'; exit ${PIPESTATUS[0]});
        [[ $? -eq 0 ]] || return 0;
        COMPREPLY=($(compgen -W "${opts}" -- ${cur}))
        __ltrim_colon_completions "$cur"

        return 0
    fi

    # completing for a command
    if [[ $cur == $com || $com == "help" ]]; then
        coms=$($script help --raw 2>/dev/null | awk '{print $1}'; exit ${PIPESTATUS[0]})
        [[ $? -eq 0 ]] || return 0;
        COMPREPLY=($(compgen -W "${coms}" -- ${cur}))
        __ltrim_colon_completions "$cur"

        return 0;
    fi
}

%%TOOLS%%
