#!/bin/sh

set -e

apk add shadow git openssh-client fish nodejs-current npm
usermod --shell /usr/bin/fish franken

sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b /usr/bin
curl https://raw.githubusercontent.com/go-task/task/main/completion/fish/task.fish > /usr/share/fish/completions/task.fish

curl --location --output /tmp/typos.tar.gz https://github.com/crate-ci/typos/releases/download/v1.16.26/typos-v1.16.26-x86_64-unknown-linux-musl.tar.gz
zcat /tmp/typos.tar.gz | tar xvf - ./typos
mv ./typos /usr/bin
rm -rf /tmp/typos.tar.gz
