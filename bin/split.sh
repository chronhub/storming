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

remote aggregate git@github.com:chronhub/aggregate.git
remote chronicler git@github.com:chronhub/chronicler.git
remote clock git@github.com:chronhub/clock.git
remote contract git@github.com:chronhub/contract.git
remote message git@github.com:chronhub/message.git
remote projector git@github.com:chronhub/projector.git
remote serializer git@github.com:chronhub/serializer.git
remote story git@github.com:chronhub/story.git
remote stream git@github.com:chronhub/stream.git
remote support git@github.com:chronhub/support.git



split 'src/Storm/Aggregate' aggregate
split 'src/Storm/Chronicler' chronicler
split 'src/Storm/Clock' clock
split 'src/Storm/Contract' contract
split 'src/Storm/Message' message
split 'src/Storm/Projector' projector
split 'src/Storm/Serializer' serializer
split 'src/Storm/Story' story
split 'src/Storm/Stream' stream
split 'src/Storm/Support' support