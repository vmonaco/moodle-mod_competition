#!/usr/bin/python3

import sys
import numpy as np
import pandas as pd
import mysql.connector
from time import time
from io import StringIO

_SOCKET = sys.argv[1]
_USER = sys.argv[2]
_PASSWD = sys.argv[3]
_DB = sys.argv[4]
_TABLE_PREFIX = sys.argv[5]
_COMPETITION = sys.argv[6]
_DATASET_USAGE = float(int(sys.argv[7])/100)

_SELECT_SCORING_TEMPLATE = "SELECT scoringtemplate FROM {}competition WHERE `id`=%s".format(_TABLE_PREFIX)
_SELECT_SUBMISSIONS = "SELECT id,userid,submission FROM {}competition_submission WHERE `compid`=%s".format(_TABLE_PREFIX)
_SCORE_UPDATE = "UPDATE `{}competition_submission` SET `score`=%s, `timescored`=%s WHERE `id`=%s".format(_TABLE_PREFIX)
_LEADERBOARD_CLEAR = "DELETE FROM {}competition_leaderboard wWHERE `compid`=%s".format(_TABLE_PREFIX)
_LEADERBOARD_UPDATE = \
"""INSERT INTO `{}competition_leaderboard` (`compid`,`userid`,`rank`,`score`) VALUES(%s, %s, %s, %s)""".format(_TABLE_PREFIX)

global conn
conn = None
global cur
cur = None

def _open_db():
    global conn
    global cur
    conn = mysql.connector.connect(unix_socket=_SOCKET, user=_USER, passwd=_PASSWD, db=_DB)
    cur = conn.cursor()
    return

def _close_db():
    global conn
    global cur
    conn.commit()
    cur.close()
    conn.close()
    conn = None
    cur = None
    return

def select_submissions(compid):
    _open_db()
    cur.execute(_SELECT_SUBMISSIONS, (compid,))
    rows = cur.fetchall()
    _close_db()
    
    for rowid,userid,submission in rows:
        data = pd.read_csv(StringIO(submission))
        yield (rowid,userid,data)

def scoring_template(compid):
    _open_db()
    cur.execute(_SELECT_SCORING_TEMPLATE, (compid,))
    rows = cur.fetchall()
    _close_db()
    df = pd.read_csv(StringIO(rows[0][0]))
    return df

def ACC1(template, submission, usage):
    '''
    ACC1 of each dataset in the template
    '''
    df = pd.merge(template, submission, on='sample', how='left', suffixes=['_template','_submission'])
    acc1 = df.groupby('dataset').apply(lambda x: (x['user_template']==x['user_submission'])[:int(usage*len(x))].sum()/len(x[:int(usage*len(x))]))
    return acc1

def column_rank(df):
    df = df.copy()
    df.sort(ascending=False)
    r = (df.diff() != 0).cumsum()
    return r

def ranks(scores):
    r = scores.apply(column_rank).sum(axis=1)
    s = scores.median(axis=1)
    rs = pd.concat([r,s], axis=1)
    # Rank by the sum of column ranks
    rs.sort([0], ascending=True, inplace=True)
    # Solve ties using the median score
    rs = rs.groupby(r).apply(lambda x: x.sort([1], ascending=False)).reset_index(level=0, drop=True)
    
    # Use the top scores from each user to rank against each other
    leaderboard = scores.loc[rs.index]
    leaderboard['rank'] = np.arange(1, len(leaderboard)+1)
    leaderboard = leaderboard.set_index('rank', append=True)
    return leaderboard

def main():
    template = scoring_template(_COMPETITION)
    scores = pd.concat({(id,userid): ACC1(template, s, _DATASET_USAGE) 
                        for id,userid,s in select_submissions(_COMPETITION)}, names=['id','userid']).unstack(level=2)
    
    # Update submission scores
    _open_db()
    for (id,userid),s in scores.iterrows():
        cur.execute(_SCORE_UPDATE, (s.to_json(double_precision=4), int(time()), id))
    _close_db()
    
    # Get the top scores within each user and rerank the leaderboard
    scores = scores.groupby(level='userid').apply(lambda x: x.apply(lambda y: y.max()))
    leaderboard = ranks(scores)
    
    # Clear the leaderboard
    cur.execute(_LEADERBOARD_CLEAR, (_COMPETITION,))
    
    # Update the leaderboard
    _open_db()
    for (userid,rank),s in leaderboard.iterrows():
        cur.execute(_LEADERBOARD_UPDATE, (_COMPETITION, userid, rank, s.to_json(double_precision=4)))
    _close_db()
    
main()