<?php
/*
  ____          _____               _ _           _       
 |  _ \        |  __ \             (_) |         | |      
 | |_) |_   _  | |__) |_ _ _ __ _____| |__  _   _| |_ ___ 
 |  _ <| | | | |  ___/ _` | '__|_  / | '_ \| | | | __/ _ \
 | |_) | |_| | | |  | (_| | |   / /| | |_) | |_| | ||  __/
 |____/ \__, | |_|   \__,_|_|  /___|_|_.__/ \__, |\__\___|
         __/ |                               __/ |        
        |___/                               |___/         
    
____________________________________
/ Si necesitas ayuda, contáctame en \
\ https://parzibyte.me               /
 ------------------------------------
        \   ^__^
         \  (oo)\_______
            (__)\       )\/\
                ||----w |
                ||     ||
Creado por Parzibyte (https://parzibyte.me). Este encabezado debe mantenerse intacto,
excepto si este es un proyecto de un estudiante.
*/
function obtenerBaseDeDatosWordPress()
{
    $pass = "";
    $usuario = "root";
    $nombreBaseDeDatos = "wp";
    $baseDeDatos = new PDO('mysql:host=localhost;dbname=' . $nombreBaseDeDatos, $usuario, $pass);
    $baseDeDatos->query("set names utf8;");
    $baseDeDatos->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
    $baseDeDatos->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $baseDeDatos->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    return $baseDeDatos;
}


function obtenerUsuarioDeWordPress($usuarioOCorreo)
{
    $bd = obtenerBaseDeDatosWordPress();
    $sentencia = $bd->prepare("SELECT * FROM wp_users 
INNER JOIN wp_usermeta ON wp_users.ID = wp_usermeta.user_id 
WHERE (wp_users.user_login = ? OR wp_users.user_email = ?) AND wp_usermeta.meta_key = ?");
    $sentencia->execute([$usuarioOCorreo, $usuarioOCorreo, "wp_capabilities"]);
    return $sentencia->fetchObject();
}

function autenticarUsuario($username, $password)
{
    # Obtener usuario. Si no existe, regresamos false inmediatamente
    $usuario = obtenerUsuarioDeWordPress($username);
    if (!$usuario) return false;
    # Incluimos PasswordHash pues vamos a realizar algunas comprobaciones
    include_once "PasswordHash.php";
    # Esta es la contraseña almacenada en la base de datos:
    $storedHash = $usuario->user_pass;
    # Crear instancia de PasswordHash
    $passwordHash = new PasswordHash(8, false);
    $metaValue = unserialize($usuario->meta_value);
    # Nota: en mi caso solo permito usuarios administradores y colaboradores; ignoro a los demás.
    # Si tú quieres permitirlos, modifica el código
    if (!isset($metaValue["administrator"]) && !isset($metaValue["contributor"])) {
        return false;
    }
    if ((isset($metaValue["administrator"]) && !$metaValue["administrator"]) && (isset($metaValue["contributor"]) && !$metaValue["contributor"])) {
        return false;
    }
    # Ahora sí comprobamos la contraseña plana ($password) con la hasheada almacenada ($storedHash)
    if ($passwordHash->CheckPassword($password, $storedHash)) {
        # Si llegamos aquí, el usuario y contraseña son correctos
        # Lo único que hacemos es poner el rol del mismo
        if ((isset($metaValue["administrator"])) && $metaValue["administrator"]) {
            $usuario->role = "administrator";
        } else if (isset($metaValue["contributor"]) && $metaValue["contributor"]) {
            $usuario->role = "contributor";
        }
        # Y regresamos el usuario
        return $usuario;
    } else {
        # Caso contrario (la contraseña no coincide) regresamos igualmente false
        return false;
    }
}

# Momento de probar. Usaré <pre> para que se vea de mejor manera, pero recuerda que solo estoy depurando ;)
echo "<pre>";
echo "Debería mostrar los datos correctos: ";
$usuarioCorrecto = autenticarUsuario("parzibyte", "hunter2");
echo json_encode($usuarioCorrecto, JSON_PRETTY_PRINT);
echo "\n\nAhora debería mostrar false: ";
$usuarioIncorrecto = autenticarUsuario("inexistente", "blabla");
echo json_encode($usuarioIncorrecto, JSON_PRETTY_PRINT);
echo "</pre>";