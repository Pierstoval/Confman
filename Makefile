SHELL=bash

# Makes all targets "PHONY" by default
MAKEFLAGS += --always-make

##
## Confman
## -------
##

.DEFAULT_GOAL := help
help: ## Show this help message
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-18s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

install: ## Sets the project up for development
	composer install

build: ## Compiles the PHAR file (alias: compile)
	box compile
compile: build

tests: ## Runs the Bats tests (alias: test)
	./test/bats/bin/bats ./test/*.bats
test: tests
