
% convert Ibarcom to Ibar
clear all
Ibarcom=[
0.102019217897 0.000024546177 0.007130222433
0.000024546177 0.083272393713 -0.001317325511
0.007130222433 -0.001317325511 0.086493014809]

m = 7.503338799400

cg = [-0.012258116293 -0.002473149928 -0.048635606065]


Ibar = Ibarcom + m*cross(cg)*(cross(cg)')
