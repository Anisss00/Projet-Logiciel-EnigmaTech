#!/bin/bash

DUREE=$(php /var/www/html/get_duration.php)

# Pas de session de jeu trouvee
if [ "$DUREE" -eq 0 ]; then
    /home/pi/piface/libpifacecad/pifacecad open blinkoff cursoroff
    /home/pi/piface/libpifacecad/pifacecad backlight on
    /home/pi/piface/libpifacecad/pifacecad clear
    /home/pi/piface/libpifacecad/pifacecad write "En attente..."
    /home/pi/piface/libpifacecad/pifacecad write "Aucune session"
    echo "Pas de session de jeu en cours."
    exit 0
fi

echo "Session active de jeu: $DUREE minutes"

# Convertir temps 
TOTAL_SECONDES=$(( DUREE * 60 ))

/home/pi/piface/libpifacecad/pifacecad open blinkoff cursoroff
/home/pi/piface/libpifacecad/pifacecad backlight on

SECONDES_RESTANTES=$TOTAL_SECONDES

while [ $SECONDES_RESTANTES -gt 0 ]; do

    MINUTES=$(( SECONDES_RESTANTES / 60 ))
    SECONDES=$(( SECONDES_RESTANTES % 60 ))
    AFFICHAGE=$(printf "%02d:%02d" $MINUTES $SECONDES)

    /home/pi/piface/libpifacecad/pifacecad clear
    /home/pi/piface/libpifacecad/pifacecad write "Temps:"
    /home/pi/piface/libpifacecad/pifacecad write "    $AFFICHAGE"

    sleep 1
    SECONDES_RESTANTES=$(( SECONDES_RESTANTES - 1 ))

done

# Fin de temps
/home/pi/piface/libpifacecad/pifacecad clear
/home/pi/piface/libpifacecad/pifacecad write "FIN"

