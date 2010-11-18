#!/bin/bash

PRWODI=$PWD
BN=$BASENAME

for file in *.ttl;
    do
        filename=$(basename $file);
        extension=${filename##*.};
        graph=${filename%.*};
        sudo /etc/fuseki/./s-put --verbose http://localhost:3030/site/data http://site/graph/$graph $file;
    done;
