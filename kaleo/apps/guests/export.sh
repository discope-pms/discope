#!/bin/bash
# rm ../web.app; rm ../manifest.json; rm ../version; cp web.app ../web.app; cp manifest.json ../manifest.json; cp version ../version

rm -rf ../../../../public/guests && mkdir ../../../../public/guests && cp -a dist/symbiose/* ../../../../public/guests/
cp version ../../../../public/guests/
