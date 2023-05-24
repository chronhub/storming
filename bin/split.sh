#!/usr/bin/env bash

set -e
set -x

CURRENT_BRANCH="main"

function split()
{
    SHA1=`./bin/splitsh-lite --prefix=$1`
    git push $2 "$SHA1:refs/heads/$CURRENT_BRANCH" -f
}

function remote()
{
    git remote add $1 $2 || true
}

#git pull origin $CURRENT_BRANCH

remote contract git@github.com:chronhub/contract.git
remote stream git@github.com:chronhub/stream.git
remote message git@github.com:chronhub/message.git
remote tracker git@github.com:chronhub/tracker.git
remote clock git@github.com:chronhub/clock.git

split 'src/Storm/Contract' contract
split 'src/Storm/Stream' stream
split 'src/Storm/Message' message
split 'src/Storm/Tracker' tracker
split 'src/Storm/Tracker' clock