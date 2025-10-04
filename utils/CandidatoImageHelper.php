<?php

namespace utils;

class CandidatoImageHelper {
    
    /**
     * Obtiene la URL de la imagen de un candidato, con fallback a imagen predeterminada
     * @param string|null $foto_candidato Ruta de la foto del candidato
     * @param bool $cache_busting Si se debe agregar timestamp para evitar cache
     * @return string URL de la imagen a mostrar
     */
    public static function obtenerImagenCandidato($foto_candidato, $cache_busting = true) {
        // Si el candidato tiene foto y el archivo existe
        if (!empty($foto_candidato) && $foto_candidato !== '0' && $foto_candidato !== 0) {
            // Convertir la ruta para verificar si el archivo existe
            $ruta_fisica = str_replace('/Login/', '', $foto_candidato);
            
            if (file_exists($ruta_fisica)) {
                // Agregar timestamp para cache-busting si está habilitado
                if ($cache_busting) {
                    $timestamp = filemtime($ruta_fisica);
                    return $foto_candidato . '?v=' . $timestamp;
                }
                return $foto_candidato;
            }
        }
        
        // Retornar imagen predeterminada (icono de usuario genérico)
        $default_image = '/Login/assets/img/candidatos/default-user-icon.svg';
        if ($cache_busting) {
            $default_path = str_replace('/Login/', '', $default_image);
            if (file_exists($default_path)) {
                $timestamp = filemtime($default_path);
                return $default_image . '?v=' . $timestamp;
            }
        }
        return $default_image;
    }
    
    /**
     * Obtiene la imagen de un candidato para mostrar en HTML
     * @param array $candidato Array con los datos del candidato
     * @param int $width Ancho de la imagen (opcional)
     * @param int $height Alto de la imagen (opcional)
     * @param string $class Clases CSS adicionales (opcional)
     * @param bool $cache_busting Si se debe agregar timestamp para evitar cache
     * @return string HTML de la imagen
     */
    public static function generarImagenHTML($candidato, $width = 50, $height = 50, $class = 'rounded-circle', $cache_busting = true) {
        $imagen_url = self::obtenerImagenCandidato($candidato['foto'] ?? null, $cache_busting);
        $nombre_candidato = htmlspecialchars($candidato['nombre'] ?? 'Candidato');
        
        return sprintf(
            '<img src="%s" alt="Foto de %s" width="%d" height="%d" class="%s" style="object-fit: cover;">',
            htmlspecialchars($imagen_url),
            $nombre_candidato,
            $width,
            $height,
            htmlspecialchars($class)
        );
    }
    
    /**
     * Verifica si un candidato tiene una foto personalizada (no la predeterminada)
     * @param string|null $foto_candidato Ruta de la foto del candidato
     * @return bool True si tiene foto personalizada, false si usa la predeterminada
     */
    public static function tieneFotoPersonalizada($foto_candidato) {
        if (empty($foto_candidato) || $foto_candidato === '0' || $foto_candidato === 0) {
            return false;
        }
        
        $ruta_fisica = str_replace('/Login/', '', $foto_candidato);
        return file_exists($ruta_fisica);
    }
}