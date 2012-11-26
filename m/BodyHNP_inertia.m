% huboplus head
% convert Ibarcom to Ibar

% Body_HNP


clear all
Ibarcom=[
0.001640330319 -0.000000108171 -0.000095397797
-0.000000108171 0.001539427875 0.000001253758
-0.000095397797 0.000001253758 0.001903925145    
]

m = 0.374250901076

cg = [0.010688581590 -0.000030086449 0.032122953618]


Ibar = Ibarcom + m*cross(cg)*(cross(cg)')