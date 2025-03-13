<?php
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
    'loginbackground'  => '',
    'carouselimages'   => [],
    'carouselinterval' => isset($theme->settings->fng_carouselinterval) ? (int)$theme->settings->fng_carouselinterval : 5000,
    'my_credit'        => get_string('credit', 'theme_fng'),
    'hasgeneralnote'   => false,
    'generalnote'      => '',
    // Variables de control para la visualización
    'has_loginimage'   => false,
    'has_loginbgcolor' => false,
    'has_carousel'     => false,
    'multiple_slides'  => false
];


// =========================================================================
// 1) Fondo o color del área de login
// =========================================================================
$loginimageurl = $theme->setting_file_url('fng_loginimage', 'fng_loginimage');
if (!empty($loginimageurl)) {
    $templatecontext['loginbackground'] = "background-image: url('{$loginimageurl}'); background-size: cover; background-position: center;";
    $templatecontext['has_loginimage'] = true;
} else {
    $loginbgcolor = !empty($theme->settings->fng_loginbg_color) ? $theme->settings->fng_loginbg_color : '#045091';
    $templatecontext['loginbackground'] = "background-color: {$loginbgcolor};";
    $templatecontext['has_loginbgcolor'] = true;
}


// =========================================================================
// 2) Carrusel de diapositivas
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
        // Verificamos si el archivo existe en storage.
        $files = $fs->get_area_files(
            $context->id,
            'theme_fng',
            "fng_slideimage{$i}",
            0,
            'sortorder',
            false
        );
        
        if (!empty($files)) {
            $validSlides++;
            
            // Extraemos los demás parámetros del slide
            $slidetitle = format_string(
                $theme->settings->{"fng_slidetitle{$i}"} ?? '',
                true,
                ['escape' => false]
            );
            $slideurl = $theme->settings->{"fng_slideurl{$i}"} ?? '#';
            
            // Se añade la diapositiva al array con sus settings
            $templatecontext['carouselimages'][] = [
                'url'       => $imageurl,
                'link'      => $slideurl,
                'title'     => $slidetitle,
                'has_title' => !empty($slidetitle),
                'has_link'  => ($slideurl !== '#'),
                'first'     => ($validSlides === 1),
                'index'     => ($validSlides - 1),
                'real_index'=> $i // Índice original del slide
            ];
        }
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
        'title'     => get_string('default_slide_title', 'theme_fng'),
        'has_title' => true,
        'has_link'  => false,
        'first'     => true,
        'index'     => 0,
        'real_index'=> 1
    ];
    $templatecontext['has_carousel'] = true;
    $templatecontext['multiple_slides'] = false;
}


// =========================================================================
// 3) Renderizar la plantilla con este contexto
// =========================================================================
echo $OUTPUT->render_from_template('theme_fng/core/login-custom', $templatecontext);