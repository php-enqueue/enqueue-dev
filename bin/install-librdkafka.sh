#!/usr/bin/env bash
git clone --depth 1 --branch v1.0.0 https://github.com/edenhill/librdkafka.git
cd librdkafka
./configure
make
sudo make install
