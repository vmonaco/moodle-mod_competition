#!/bin/env python

import sys
import pandas as pd

import sys
import pandas as pd
import mysql.connector

template = pd.read_csv(sys.argv[1])

try:
    submission = pd.read_csv(sys.argv[2])
except e:
    sys.exit('Unable to parse submission: {}'.format(e))
    
# Match the number of lines, including the header
if len(template) != len(submission):
    sys.exit('Submission should contain {} lines'.format(len(submission)+1))
    
if len(pd.merge(template, submission, on='sample', how='inner')) != len(template):
    sys.exit('Missing classifications for some samples')
    
# Passed validation
sys.exit(0)