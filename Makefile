.PHONY: all install nightly
all: install
	grunt build
install:
	git submodule update --init --recursive
	(cd js/jsxc/ && npm install)
	(cd js/jsxc/ && bower install)
	npm install
VERSION_STRING:=nightly.$(shell date +%Y%m%d)-$(shell git log -1 --pretty=format:"%h")
nightly:
	git submodule update --init --recursive
	(cd js/jsxc/ && git pull origin master && git submodule update)
	(cd js/jsxc/ && npm install && bower install)
	echo {} > js/jsxc/.github.json
	(cd js/jsxc/ && grunt build:prerelease --ver=$(VERSION_STRING) --force)
	npm install
	grunt build:prerelease --ver=$(VERSION_STRING)
	mv archives/ojsxc-$(VERSION_STRING).tar.gz archives/ojsxc-nightly-latest.tar.gz
