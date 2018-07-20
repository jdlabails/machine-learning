var car;
var carLenght = 50;
var carWidth = 20;

var canvas;
var imgElement = document.getElementById('green-car');
var loop;

function goForward() {
    var angleRad = Math.PI * car.angle / 180;
    car.top += Math.sin(angleRad);
    car.left += Math.cos(angleRad);
}

function goBackward() {
    var angleRad = Math.PI * car.angle / 180;
    car.top -= Math.sin(angleRad);
    car.left -= Math.cos(angleRad);
}

function turn(rotDirection, moveDirection) {

    c = 1;
    var angleRad = Math.PI * car.angle / 180;

    // on se met sur l'axe
    car.left -= rotDirection * Math.sin(angleRad) * turnFactor;
    car.top += rotDirection * Math.cos(angleRad) * turnFactor;

    // on tourne
    car.angle += moveDirection * turnValueDeg;

    angleRad += moveDirection * turnValueRad;

    // on se met au left arriere
    car.left += rotDirection * Math.sin(angleRad) * turnFactor;
    car.top -= rotDirection * Math.cos(angleRad) * turnFactor;

}

function move(i) {
    switch (i) {
        case 0 :
            goForward();
            break;
        case 1 :
            goBackward();
            break;
        case 2 :
            turn(1, 1);
            break;
        case 3 :
            turn(1, -1);
            break;
        case 4 :
            turn(-1, 1);
            break;
        case 5 :
            turn(-1, -1);
            break;
    }
    canvas.renderAll();
    printPosition();
}

function printPosition() {
    car.angle = car.angle % 360;
    console.log(car.angle);

    var angleRad = Math.PI * car.angle / 180;

    var x = car.left;
    var y = car.top;

    $('#back-left').html('Back Left : ' + x + ' , ' + y);

    var xbr = carWidth * Math.cos(Math.PI / 2 + angleRad) + x;
    var ybr = carWidth * Math.sin(Math.PI / 2 + angleRad) + y;
    $('#back-right').html('Back right : ' + xbr + ' , ' + ybr);

    var xfl = carLenght * Math.cos(angleRad) + x;
    var yfl = carLenght * Math.sin(angleRad) + y;
    $('#front-left').html('Front left : ' + xfl + ' , ' + yfl);

    var xfr = carLenght * Math.cos(angleRad) + xbr;
    var yfr = carLenght * Math.sin(angleRad) + ybr;
    $('#front-right').html('Front right : ' + xfr + ' , ' + yfr);

    var centerY = y + Math.cos(angleRad) * carWidth / 2 + Math.sin(angleRad) * carLenght / 2;
    $('#center-y').html('Center Y : ' + centerY);
}

function setPosition(x, y, thetaIndex) {
    car.top = y;
    car.left = x;
    car.angle = thetaIndex * turnValueDeg;
    canvas.renderAll();
}

(function () {
    canvas = new fabric.Canvas('playGround');
    car = new fabric.Image(imgElement, {
        left: leftCarPosition,
        top: topCarPosition,
        angle: 0,
        opacity: 0.85
    });

    var parkedCars = new fabric.Rect({
        width: 200, height: 20, left: 100, top: 80, angle: 0,
        fill: 'rgba(0,200,0,0.5)'
    });

    //canvas.add(car, parkedCars);
    canvas.add(car);
    canvas.renderAll();

    $('#nbMoves').html(moves.length);
    $('#currentMoves').html(0);


    console.log(car, moves);

//        loop = setInterval(function(){
//            var action = moves.shift();
//            console.log(action, moves);
//            move(action);
//
//            console.log(car);
//            canvas.renderAll();
//
//            if (moves.length == 0) {
//                clearInterval(loop);
//            }
//        }, 100);

    var indexMove = 0;
    var indexPosition = 0;
    document.onkeydown = function (evt) {
        evt = evt || window.event;
        console.log(evt.keyCode);

//            if(evt.keyCode == 37 && indexMove > 0) {
//                indexMove--;
//                move(moves[indexMove]);
//                console.log(indexMove, moves, car);
//            }
//            if(evt.keyCode == 39 && indexMove < moves.length) {
//                indexMove++;
//                move(moves[indexMove]);
//                console.log(indexMove, moves, car);
//            }

        if (evt.keyCode == 37 && indexPosition > 0) {
            indexPosition--;
            move(positions[ indexPosition ]);
            console.log(indexPosition, positions, car);
        }
        if (evt.keyCode == 39 && indexPosition < positions.length) {
            indexPosition++;
            move(positions[ indexPosition ]);
            console.log(indexPosition, positions, car);
        }

        if (evt.keyCode == 13) {
            loop = setInterval(function () {
                $('#currentMove').html(++indexMove);
                printPosition();

                if ($('#playMode').val() === 'moves') {
                    var action = moves.shift();
                    console.log(action, moves);
                    move(action);

                    if (moves.length == 0) {
                        clearInterval(loop);
                    }
                }

                if ($('#playMode').val() === 'positions') {
                    var position = positions.shift();
                    setPosition(position.X, position.Y, position.thetaIndex);

                    if (positions.length == 0) {
                        clearInterval(loop);
                    }

                    console.log(position, car);
                }

            }, 50);
        }

        if (evt.keyCode == 27) {
            clearInterval(loop);
        }

        if (evt.keyCode > 95) {
            console.log(evt.keyCode - 96);
            move(evt.keyCode - 96);
            console.log(car);

        }

    };
})();