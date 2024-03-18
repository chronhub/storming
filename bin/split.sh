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

git pull origin $CURRENT_BRANCH

remote clock git@github.com:chronhub/clock.git
remote contract git@github.com:chronhub/contract.git
remote chronicler git@github.com:chronhub/chronicler.git
remote message git@github.com:chronhub/message.git
remote reporter git@github.com:chronhub/reporter.git
remote serializer git@github.com:chronhub/serializer.git
remote stream git@github.com:chronhub/stream.git
remote support git@github.com:chronhub/support.git
remote tracker git@github.com:chronhub/tracker.git
remote projector git@github.com:chronhub/projector.git

split 'src/Storm/Clock' clock
split 'src/Storm/Contract' contract
split 'src/Storm/Chronicler' chronicler
split 'src/Storm/Message' message
split 'src/Storm/Reporter' reporter
split 'src/Storm/Serializer' serializer
split 'src/Storm/Stream' stream
split 'src/Storm/Support' support
split 'src/Storm/Tracker' tracker
split 'src/Storm/Projector' projector