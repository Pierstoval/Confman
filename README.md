Projects config manager
=======================

A command-line tool to manage different projects / repositories on your machine.

## Requirements

- PHP 8.4+
- Composer
- Ideally, using this tool on a Linux/Unix server

## Install

- Clone the repo
- Run the `composer install` command
- Run the `cp projects.json.example projects.json` command

## Usage

Everything is set in the `run` PHP file.

You can either use `./run` or `php run` to execute it.

### Tips

This tool uses the Symfony Console command. Meaning you can always use the `--help` option on **any** command to get information about it, and you can always use the `list` command to get the list of all available commands.

### Add a new project to the list

You have two choices:

- Manually update the `projects.json` file and add a project.<br>Mandatory fields are `name` and `path` (for now, more might come in the future)
- Run the `project:add` or `add` interactive command, it will update the `projects.json` file automatically

```
 ❯ ./run projects:add

 Project name?:
 > MyProject

 Path?:
 > /var/www/html/my-project


 [OK] Done!

```

### List all projects

Run the `projects:list` or just `projects` command.

```
 ❯ ./run projects

Detected projects file: /home/myself/ConfigManager/projects.json
------------------------------------------------------------

 ---------------------- ----------------------------- ---------------- --------------------------- -------------------------------------
  Name                   Path                          Current branch   Last commit date            Current remote URL
 ---------------------- ----------------------------- ---------------- --------------------------- -------------------------------------
  My Project             /home/myself/MyProject        main             2025-11-03 13:23:22 +0100   git@github.com:MyOrg/MyProject.git
  My other project       /home/myself/MyOtherProject   develop          2024-12-08 09:45:59 +0100   https://github.com/MyOrg/MyProj.git
 ---------------------- ----------------------------- ---------------- --------------------------- -------------------------------------

```
