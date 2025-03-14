<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Login page layout for theme_fng.
 *
 * @package    theme_fng
 * @copyright  2025 Soporte fng <soporte@fng.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Obtener atributos del body y la configuración del tema.
$bodyattributes = $OUTPUT->body_attributes();
$theme = theme_config::load('fng');

// Para el manejo de archivos.
$fs = get_file_storage();
$context = context_system::instance();

// Construimos el contexto para la plantilla.
$templatecontext = [
    'sitename'         => format_string(
        $SITE->fullname,
        true,
        ['context' => context_course::instance(SITEID), 'escape' => false]
    ),
    'output'           => $OUTPUT,
    'bodyattributes'   => $bodyattributes,
    'carouselimages'   => [],
    'carouselinterval' => isset($theme->settings->fng_carouselinterval) ? (int)$theme->settings->fng_carouselinterval : 5000,
    'my_credit'        => get_string('credit', 'theme_fng'),
    'hasgeneralnote'   => false,
    'generalnote'      => '',
    // Variables de control para la visualización
    'has_carousel'     => false,
    'multiple_slides'  => false
];


// =========================================================================
// Carrusel de diapositivas
// =========================================================================
// Se obtiene el número de slides configurado con el prefijo "fng_".
$numslides = isset($theme->settings->fng_numberofslides) && is_numeric($theme->settings->fng_numberofslides)
    ? (int)$theme->settings->fng_numberofslides
    : 1;

$validSlides = 0; // Contador de slides válidos

for ($i = 1; $i <= $numslides; $i++) {
    // Obtenemos la URL de la imagen 
    $imageurl = $theme->setting_file_url("fng_slideimage{$i}", "fng_slideimage{$i}");
    
    if (!empty($imageurl)) {
        $validSlides++;
        
        // Extraer título solo si existe
        $slidetitle = '';
        if (isset($theme->settings->{"fng_slidetitle{$i}"}) && !empty($theme->settings->{"fng_slidetitle{$i}"})) {
            $slidetitle = format_string(
                $theme->settings->{"fng_slidetitle{$i}"},
                true,
                ['escape' => false]
            );
        }
        
        // Extraer URL solo si existe
        $slideurl = '#';
        if (isset($theme->settings->{"fng_slideurl{$i}"}) && !empty($theme->settings->{"fng_slideurl{$i}"})) {
            $slideurl = $theme->settings->{"fng_slideurl{$i}"};
        }
        
        // Se añade la diapositiva al array con sus settings
        $templatecontext['carouselimages'][] = [
            'url'       => $imageurl,
            'link'      => $slideurl,
            'title'     => $slidetitle,
            'has_title' => !empty($slidetitle),
            'has_link'  => !empty($slideurl) && $slideurl !== '#',
            'first'     => ($validSlides === 1),
            'index'     => ($validSlides - 1),
            'real_index'=> $i // Índice original del slide
        ];
    }
}

// Actualizamos las variables de control del carrusel
$templatecontext['has_carousel'] = ($validSlides > 0);
$templatecontext['multiple_slides'] = ($validSlides > 1);

// Si no hay slides válidos, usamos una imagen por defecto
if (!$templatecontext['has_carousel']) {
    $defaultImage = $OUTPUT->image_url('slide0', 'theme_fng');
    $templatecontext['carouselimages'][] = [
        'url'       => (string)$defaultImage,
        'link'      => '#',
        'title'     => get_string('default_slide_title', 'theme_fng', ['fallback' => 'Bienvenido']),
        'has_title' => true,
        'has_link'  => false,
        'first'     => true,
        'index'     => 0,
        'real_index'=> 1
    ];
    $templatecontext['has_carousel'] = true;
    $templatecontext['multiple_slides'] = false;
}

// Depuración opcional para verificar la configuración correcta
if (debugging()) {
    debugging('Slides configurados: ' . count($templatecontext['carouselimages']), DEBUG_DEVELOPER);
    foreach ($templatecontext['carouselimages'] as $index => $slide) {
        debugging("Slide " . ($index + 1) . ": title=" . ($slide['has_title'] ? 'visible' : 'hidden') . 
                ", link=" . ($slide['has_link'] ? 'enabled' : 'disabled'), DEBUG_DEVELOPER);
    }
}

// =========================================================================
// Renderizar la plantilla con este contexto
// =========================================================================
echo $OUTPUT->render_from_template('theme_fng/core/login-custom', $templatecontext);