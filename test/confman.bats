setup() {
    load 'test_helper/bats-support/load'
    load 'test_helper/bats-assert/load'

    # get the containing directory of this file
    # use $BATS_TEST_FILENAME instead of ${BASH_SOURCE[0]} or $0,
    # as those will point to the bats executable's location or the preprocessed file respectively
    ROOT="$( cd "$( dirname "$BATS_TEST_FILENAME" )/.." >/dev/null 2>&1 && pwd )"

    rm -rf "${ROOT}/test/configs/*"
}

@test "can list commands" {
    run ./output/confman.phar

    assert_success
    assert_line  --regexp --index  0 'Confman v[0-9]+\.[0-9]+\.[0-9]+'

    assert_line --index  1 'Usage:'
    assert_line --index  2 '  command [options] [arguments]'

    assert_line --index  3 'Options:'
    assert_line --index  4 '  -h, --help             Display help for the given command. When no command is given display help for the list command'
    assert_line --index  5 '      --silent           Do not output any message'
    assert_line --index  6 '  -q, --quiet            Only errors are displayed. All other output is suppressed'
    assert_line --index  7 '  -V, --version          Display this application version'
    assert_line --index  8 '      --ansi|--no-ansi   Force (or disable --no-ansi) ANSI output'
    assert_line --index  9 '  -n, --no-interaction   Do not ask any interactive question'
    assert_line --index 10 '  -c, --config[=CONFIG]  Path to look for the "confman.json" file. Can be a file or a directory.'
    assert_line --index 11 '  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'

    assert_line --index 12 'Available commands:'
    assert_line --index 13 '  completion            Dump the shell completion script'
    assert_line --index 14 '  help                  Display help for a command'
    assert_line --index 15 '  list                  List commands'
    assert_line --index 16 ' config'
    assert_line --index 17 '  config:create         [create] Create a new configuration file'
    assert_line --index 18 '  config:list           [configs] Lists all configuration files'
    assert_line --index 19 ' git'
    assert_line --index 20 '  git:fetch             Runs the "git fetch --all --prune" command on all projects.'
    assert_line --index 21 ' projects'
    assert_line --index 22 '  projects:add          [add] Add a new project to configuration'
    assert_line --index 23 '  projects:command:all  [command|commands] Run a command on all projects'
    assert_line --index 24 '  projects:list         [projects] Lists all currently configured projects'
}

@test "shows warning when no config is available" {
    run ./output/confman.phar projects
    assert_failure 1

    assert_line --index 0 'In App.php line 144:'
    assert_line --index 1 '                                                                '
    assert_line --index 2 '  Config manager file was not found.                            '
    assert_line --index 3 '  Searched in these paths:                                      '
    assert_line --index 4 '   - /var/www/confman/confman.json                              '
    assert_line --index 5 '   - /home/pierstoval/.config/Confman/confman.json              '
    assert_line --index 6 '                                                                '
    assert_line --index 7 '  Create any of these files to start configuring your setup 🚀  '
    assert_line --index 8 '                                                                '
}
