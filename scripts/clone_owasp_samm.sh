#!/bin/sh
GITHUB_REPO=https://github.com/owaspsamm/core.git
BRANCH=main

if [ ! -d "private/core" ]; then
    mkdir -p private
    cd private
    echo 'Cloning project...'
    git clone --single-branch -b $BRANCH $GITHUB_REPO
    cd ..
else
  cd private/core
  echo 'Discarding local changes to OWASP SAMM model (if any)...'
  git checkout $BRANCH
  git checkout -- .
  echo 'Pulling latest changes...'
  git pull
  cd ..
  cd ..
fi
