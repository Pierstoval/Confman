Projects config manager
=======================

A command-line tool to manage different projects / repositories on your machine.

## Requirements

- PHP 8.4+

For development (or to install from source):

- [Composer](https://getcomposer.org/)
- [Box](https://github.com/box-project/box) installed globally
- Ideally, using this tool on a Linux/Unix server, 

## Install

### From source

- Clone the repo with `git clone git@github.com:Pierstoval/Confman.git`
- Go to the project's directory via `cd Confman`
- Run the `composer install` command
- Run the `box compile` command
- Your executable is in the `output/confman.phar` file 🚀

### From latest releases

- Go to the latest release: https://github.com/Pierstoval/Confman/releases/latest
- Unwrap the `Assets` section
- Download the `confman.phar` file
- Save it in your computer 🚀

## Usage

Everything is set in the `run` PHP file.

You can either use `./run` or `php run` to execute it.

If you install it globally, you can rename the file to `confman` and add it somewhere accessible via your `PATH` environment variable.

### Tips

- This tool uses the Symfony Console command. Meaning you can always use the `--help` option on **any** command to get information about it, and you can always use the `list` command to get the list of all available commands.
- Use the `-c`/`--config` option to create several configs. It can be useful when running similar commands on projects with the same stack. You can, for instance, create a `npm` category for all projects using NPM as package manager, or `php` for projects using Composer.

### Create configuration files

You cannot use Confman is there is no configuration file for a project.

To create a config file, you have several options:

- Create a `confman.json` with empty content, either:
  - In the current directory
  - In the `$HOME/.config/Confman/` directory
- Run the `confman create` command

The `confman create` command suggest accessible directories from your current execution path.

You can also **categorize** config files, so you can have multiple "projects", by using the `-c`/`--config` option.

Examples:

```
 > confman create

 Where do you want to create the config file?:
  [0] /home/my-user/current_directory/confman.json
  [1] /home/my-user/.config/Confman/confman.json
 > 
 > confman create -c some_category

 Where do you want to create the config file?:
  [0] /home/my-user/current_directory/some_category.json
  [1] /home/my-user/.config/Confman/some_category.json
 > 
```

### Add a new project to the list

You have two choices:

- Manually update the `confman.json` file and add a project.<br>Mandatory fields are `name` and `path` (for now, more might come in the future)
- Run the `project:add` or `add` interactive command, it will update the `confman.json` file automatically

```
 > confman projects:add

 Project name?:
 > MyProject

 Path?:
 > /var/www/html/my-project


 [OK] Done!

```

Or by directly adding the `name` and `path` as arguments:

```
 > confman projects:add MyProject /var/www/html/my-project

 [OK] Done!

```

### List all projects

Run the `projects:list` or just `projects` command.

```
 > confman projects

Detected projects file: /home/myself/Confman/confman.json
------------------------------------------------------------

 ---------------------- ----------------------------- ---------------- --------------------------- -------------------------------------
  Name                   Path                          Current branch   Last commit date            Current remote URL
 ---------------------- ----------------------------- ---------------- --------------------------- -------------------------------------
  My Project             /home/myself/MyProject        main             2025-11-03 13:23:22 +0100   git@github.com:MyOrg/MyProject.git
  My other project       /home/myself/MyOtherProject   develop          2024-12-08 09:45:59 +0100   https://github.com/MyOrg/MyProj.git
 ---------------------- ----------------------------- ---------------- --------------------------- -------------------------------------

```

### Run a particular command on **all** projects

Run the `projects:command:all` command or one of its aliases `command` or `command:all`.

```
> confman projects:command:all -- git fetch --all --prune

Running /bin/git fetch --all --prune on all projects…
-----------------------------------------------------

 ---------------------- ----------------------------- ------------------
  Project                Path                          Status
 ---------------------- ----------------------------- ------------------
  My Project             /home/myself/MyProject               ✅
  My other project       /home/myself/MyOtherProject          ✅
 ---------------------- ----------------------------- ------------------


 [OK] Done!


```

## Helper/quick project commands

### Git fetch

Runs the `git fetch --all --prune` command on all projects in parallel.

```
> confman proj:git:fetch

Running /bin/git fetch --all --prune on all projects…
-----------------------------------------------------

 ---------------------- ----------------------------- ------------------
  Project                Path                          Status
 ---------------------- ----------------------------- ------------------
  My Project             /home/myself/MyProject               ✅
  My other project       /home/myself/MyOtherProject          ✅
 ---------------------- ----------------------------- ------------------


 [OK] Done!


```
