#!/usr/bin/bash


>/dev/tcp/127.0.0.1/9000 && exit 0 || exit 1
