#!/usr/bin/env zsh

_typo3console()
{
    local state com cur commands options

    cur=${words[${#words[@]}]}

    # lookup for command
    for word in ${words[@]:1}; do
        if [[ $word != -* ]]; then
            com=$word
            break
        fi
    done

    [[ ${cur} == --* ]] && state="option"

    [[ $cur == $com || $com == "help" ]] && state="command"

    case $state in
        command)
            commands=("${(@f)$(${words[1]} help --raw 2>/dev/null | awk '{print $1}')}")
            _describe 'command' commands
        ;;
        option)
            options=("${(@f)$(${words[1]} help ${words[2]} 2>/dev/null | sed -n '/Options/,/^$/p' | sed -e '1d;$d' | sed 's/[^--]*\(--.*\)/\1/' | sed -En 's/[^ ]*(-(-[[:alnum:]]+){1,}).*/\1/p' | awk '{$1=$1};1')}")
            _describe 'option' options
        ;;
        *)
            # fallback to file completion
            _arguments '*:file:_files'
    esac
}

%%TOOLS%%
