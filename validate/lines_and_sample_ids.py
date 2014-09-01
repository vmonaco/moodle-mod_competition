#!/usr/bin/python3

import sys
import pandas as pd
import mysql.connector
from io import StringIO

if len(sys.argv) != 8:
    sys.exit('Expected 7 arguments, got {}'.format(len(sys.argv)))
    
_SOCKET = sys.argv[1]
_USER = sys.argv[2]
_PASSWD = sys.argv[3]
_DB = sys.argv[4]
_TABLE_PREFIX = sys.argv[5]
_COMPETITION = sys.argv[6]
_SUBMISSION= sys.argv[7]

_SELECT_SCORING_TEMPLATE = "SELECT scoringtemplate FROM {}competition WHERE `id`=%s".format(_TABLE_PREFIX)

try:
    conn = mysql.connector.connect(unix_socket=_SOCKET, user=_USER, passwd=_PASSWD, db=_DB)
    cur = conn.cursor()
    cur.execute(_SELECT_SCORING_TEMPLATE, (_COMPETITION,))
    rows = cur.fetchall()
    template = pd.read_csv(StringIO(rows[0][0]))
    cur = conn.cursor()
    conn.commit()
    cur.close()
    conn.close()
except Exception as e:
    sys.exit('Unable to locate scoring template. Please try again later.')

try:
    submission = pd.read_csv(_SUBMISSION)
except Exception as e:
    sys.exit('Unable to parse submission: {}'.format(e))

import IPython
IPython.embed()
# Match the number of lines, including the header
if len(template) != len(submission):
    sys.exit('Your file contains {} lines. Submissions should contain {} lines including the header'.format(len(submission), len(template)+1))
    
if len(pd.merge(template, submission, on='sample', how='inner')) != len(template):
    sys.exit('Missing classifications for some samples')
    
# Passed validation
sys.exit(0)