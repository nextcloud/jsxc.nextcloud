.PHONY: all install
all: install
	grunt build
install:
	git submodule update --init --recursive --remote
	(cd js/jsxc/ && npm install)
	(cd js/jsxc/ && bower install)
	npm install
