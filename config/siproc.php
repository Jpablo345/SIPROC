<?php

/*
 * Ajustes propios de SIPROC.
 */

return [

    /*
     * Estilo del menú lateral, siguiendo el manual de identidad UFPSO:
     *   "rojo"  fondo rojo oscuro institucional (#801418) con el logo blanco a una tinta (por defecto).
     *           Hace contraste con el contenido, que va sobre fondo claro.
     *   "claro" fondo blanco con acentos rojos y el logo a color.
     * ("oscuro" se acepta como sinónimo de "rojo" por compatibilidad.)
     */
    'menu' => env('SIPROC_MENU', 'rojo'),

    /*
     * Foto del panel izquierdo del login. Si el archivo existe en public/, se muestra;
     * si no, el panel queda solo con el texto institucional.
     * El manual pide fotos luminosas, de buena calidad y con emoción positiva.
     */
    'foto_login' => env('SIPROC_FOTO_LOGIN', 'img/login-campus.jpg'),

];
