#!/usr/bin/python

import numpy as np

def train_features():
    return np.array([[ 2.17760051e-03,  1.14735503e+02,  9.78596575e-01,  2.68036215e+03,
        4.26111241e-01,  4.04854145e+02,  1.06067131e+04,  7.75457343e+00,
        1.07036468e+01,  6.07958858e-04,  3.48924038e+00, -4.21133246e-01,
        9.98062930e-01], [ 7.02609993e-02,  5.98249663e+01,  9.79903971e-01,  1.48854559e+03,
        6.97172020e-01,  4.55783662e+02,  5.89435741e+03,  6.32437530e+00,
        7.96739231e+00,  1.50620376e-03,  2.32299236e+00, -5.86097768e-01,
        9.99273473e-01], [ 1.34120654e-03,  2.18952446e+02,  9.38062339e-01,  1.76802091e+03,
        3.19912664e-01,  3.56688243e+02,  6.85313119e+03,  7.83468375e+00,
        1.15160126e+01,  4.33628520e-04,  4.00872475e+00, -3.20684123e-01,
        9.93066714e-01], [ 2.56215365e-03,  4.54586284e+01,  9.83037447e-01,  1.34000164e+03,
        3.99172552e-01,  3.90069973e+02,  5.31454794e+03,  7.53210072e+00,
        1.04823112e+01,  5.79724345e-04,  3.35739741e+00, -4.05206350e-01,
        9.97242820e-01], [ 5.33750974e-03,  9.11723885e+00,  9.98157746e-01,  2.47447111e+03,
        5.84730866e-01,  4.27174461e+02,  9.88876720e+03,  7.45178306e+00,
        9.18049458e+00,  1.08151725e-03,  2.35643086e+00, -5.87497344e-01,
        9.99728248e-01], [ 1.72579174e-02,  4.55574909e+01,  9.89976979e-01,  2.27261813e+03,
        5.65296843e-01,  4.25365042e+02,  9.04491505e+03,  7.05543885e+00,
        9.16984264e+00,  9.57406479e-04,  2.81631153e+00, -5.19110756e-01,
        9.99102222e-01], [ 1.41525811e-02,  2.41907131e+01,  9.88002143e-01,  1.00808557e+03,
        5.77905163e-01,  4.24436837e+02,  4.00815155e+03,  6.84309289e+00,
        8.86887880e+00,  1.00336636e-03,  2.71288286e+00, -5.09613574e-01,
        9.98656853e-01], [ 6.66937325e-01,  3.45454758e+01,  9.82094789e-01,  9.64634474e+02,
        8.80016714e-01,  4.89530623e+02,  3.82399242e+03,  2.11930894e+00,
        2.71887631e+00,  2.81259721e-03,  1.18288579e+00, -5.92909135e-01,
        9.47806210e-01], [ 8.22914312e-03,  1.15754045e+02,  9.77733903e-01,  2.59929324e+03,
        5.17414259e-01,  4.20759002e+02,  1.02814189e+04,  7.56841812e+00,
        1.02037480e+01,  8.04292176e-04,  3.21980364e+00, -4.69614477e-01,
        9.98897573e-01], [ 4.12390636e-01,  8.95255287e+01,  9.72457427e-01,  1.62517427e+03,
        7.64234319e-01,  4.62876733e+02,  6.41117154e+03,  3.79744264e+00,
        5.13033439e+00,  1.99401617e-03,  2.07097975e+00, -5.09912421e-01,
        9.84647796e-01], [ 4.65927353e-01,  1.78756548e+01,  9.88047300e-01,  7.47736516e+02,
        8.50575619e-01,  4.86385899e+02,  2.97307041e+03,  3.27931718e+00,
        4.04417221e+00,  2.51370918e-03,  1.39212320e+00, -6.35111463e-01,
        9.88121047e-01], [ 6.43343416e-01,  9.90086649e+01,  9.34677696e-01,  7.57815223e+02,
        8.57152287e-01,  4.96135493e+02,  2.93225223e+03,  2.06481825e+00,
        2.80236930e+00,  2.68698961e-03,  1.44917803e+00, -4.14094956e-01,
        8.75556199e-01], [ 1.14866896e-01,  1.86105441e+01,  9.97209470e-01,  3.33465884e+03,
        7.87078666e-01,  3.70873147e+02,  1.33200248e+04,  5.80578026e+00,
        6.80138619e+00,  1.95066721e-03,  1.62630915e+00, -6.88780812e-01,
        9.99581742e-01], [ 1.98695591e-02,  1.25550594e+02,  9.61873161e-01,  1.64643449e+03,
        6.17265918e-01,  4.64106558e+02,  6.46018736e+03,  6.44878607e+00,
        8.49181731e+00,  1.14598689e-03,  2.81859679e+00, -4.85880708e-01,
        9.97638900e-01], [ 6.87574992e-02,  3.33480925e+01,  9.92713883e-01,  2.28846700e+03,
        6.15668919e-01,  4.14721890e+02,  9.12051990e+03,  6.42591697e+00,
        8.27332624e+00,  1.12846785e-03,  2.58719650e+00, -5.44276346e-01,
        9.98875417e-01], [ 6.52371825e-02,  1.57319041e+01,  9.96710765e-01,  2.39155972e+03,
        6.61158209e-01,  3.67236805e+02,  9.55050696e+03,  6.53004143e+00,
        8.04993652e+00,  1.30189758e-03,  2.18062492e+00, -6.08688490e-01,
        9.99524827e-01], [ 3.95981837e-01,  5.85354680e+01,  9.64486256e-01,  8.24096279e+02,
        9.01431445e-01,  4.84984143e+02,  3.23784965e+03,  2.96216096e+00,
        3.53187671e+00,  2.99324750e-03,  1.05223596e+00, -7.13707735e-01,
        9.89961417e-01], [ 2.41128608e-03,  7.47987489e+00,  9.99299399e-01,  5.33837598e+03,
        5.45095406e-01,  2.95624507e+02,  2.13460241e+04,  8.32676827e+00,
        1.01726812e+01,  1.04024271e-03,  2.35190550e+00, -6.13926789e-01,
        9.99932235e-01], [ 3.17902929e-02,  2.14388678e+01,  9.98427587e-01,  6.81710967e+03,
        5.03024208e-01,  3.43521247e+02,  2.72469998e+04,  7.66937981e+00,
        9.80887523e+00,  7.77927394e-04,  2.89188612e+00, -5.65514102e-01,
        9.99771450e-01], [ 1.06207629e-01,  2.32441194e+02,  9.59167304e-01,  2.84578063e+03,
        4.13499907e-01,  1.65866226e+02,  1.11506813e+04,  6.42983787e+00,
        9.50444989e+00,  5.73213093e-04,  4.14708774e+00, -3.48379232e-01,
        9.90750585e-01], [ 1.92027084e-01,  1.36865115e+02,  9.83237201e-01,  4.08175526e+03,
        5.66500420e-01,  4.03665257e+02,  1.61901559e+04,  5.42094497e+00,
        7.60718556e+00,  1.01555282e-03,  3.23799182e+00, -4.31000178e-01,
        9.91911322e-01], [ 1.82988206e-01,  6.37714308e+01,  9.85010076e-01,  2.12675594e+03,
        6.01912835e-01,  3.78276097e+02,  8.44325233e+03,  5.65843428e+00,
        7.60780936e+00,  1.11853245e-03,  2.83933850e+00, -4.88734076e-01,
        9.96241322e-01], [ 9.33045936e-02,  6.92688099e+02,  9.21573638e-01,  4.41556474e+03,
        3.96357421e-01,  3.85809986e+02,  1.69695709e+04,  6.82427116e+00,
        1.02949311e+01,  5.03459004e-04,  4.64686211e+00, -3.00932962e-01,
        9.86108974e-01], [ 2.28750595e-01,  6.22372511e+00,  9.98519203e-01,  2.10140391e+03,
        7.83405937e-01,  3.53608438e+02,  8.39939193e+03,  5.07153026e+00,
        6.01565766e+00,  1.93660034e-03,  1.64490161e+00, -6.81005997e-01,
        9.98973881e-01], [ 2.27144439e-03,  4.25947368e+01,  9.93936643e-01,  3.51223804e+03,
        4.63147457e-01,  3.12189969e+02,  1.40063574e+04,  8.33345453e+00,
        1.08307359e+01,  7.20540782e-04,  3.15667456e+00, -5.30373469e-01,
        9.99783780e-01], [ 7.06808020e-02,  1.91387690e+02,  9.81851107e-01,  5.27269245e+03,
        5.47929527e-01,  2.51486623e+02,  2.08993821e+04,  6.43632076e+00,
        8.64974370e+00,  8.88708831e-04,  3.24423193e+00, -4.55029456e-01,
        9.96756262e-01], [ 2.57702907e-03,  8.36760455e+01,  9.89724197e-01,  4.07092456e+03,
        4.36508278e-01,  3.67482414e+02,  1.62000222e+04,  7.44340971e+00,
        1.00320773e+01,  7.78530919e-04,  2.94906898e+00, -4.49471947e-01,
        9.98405598e-01], [ 1.95611580e-03,  1.00123917e+01,  9.98884609e-01,  4.48822697e+03,
        4.63429785e-01,  2.85093270e+02,  1.79428955e+04,  8.09647417e+00,
        1.02814102e+01,  9.39622317e-04,  2.59731891e+00, -5.53610818e-01,
        9.99795462e-01], [ 5.43568787e-03,  2.28879871e+01,  9.97638855e-01,  4.84654771e+03,
        4.44096886e-01,  3.73829613e+02,  1.93633028e+04,  7.07349167e+00,
        9.35968118e+00,  7.57035491e-04,  2.77245725e+00, -4.62044442e-01,
        9.98125358e-01], [ 4.84684437e-04,  1.66186534e+02,  9.89488047e-01,  7.90455605e+03,
        1.39671958e-01,  2.95956402e+02,  3.14520377e+04,  7.96899779e+00,
        1.23089587e+01,  2.15521514e-04,  4.48244387e+00, -2.80479022e-01,
        9.90917979e-01], [ 6.20561091e-02,  4.69436702e+02,  9.78530938e-01,  1.09339997e+04,
        4.13804606e-01,  2.04450628e+02,  4.32665620e+04,  6.15850351e+00,
        8.97565122e+00,  5.47356747e-04,  3.77077605e+00, -3.44088956e-01,
        9.87799297e-01]])

def train_labels():
    return np.array(['passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'passport', 'photo', 'photo', 'photo', 'photo', 'photo', 'photo', 'photo', 'photo', 'photo', 'photo', 'photo', 'photo', 'photo', 'photo'])

