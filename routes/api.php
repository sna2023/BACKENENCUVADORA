<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\AsesoriaController;
use App\Http\Controllers\EmprendedorController;
use App\Http\Controllers\NotificacionController;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/user',    fn(Request $r) => $r->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard por rol
    Route::get('/dashboard', [ProjectController::class, 'dashboard']);

    // Proyectos
    Route::post('/projects',                          [ProjectController::class, 'store']);
    Route::put('/projects/{project}/evaluate',        [ProjectController::class, 'evaluate']);
    Route::post('/projects/{project}/assign-mentor',  [ProjectController::class, 'assignMentor']);
    Route::post('/projects/{project}/submit',         [ProjectController::class, 'submit']);
    Route::get('/projects/{project}/submissions',     [ProjectController::class, 'submissions']);

    // Proyectos
    Route::get('/proyectos',                           [ProyectoController::class, 'index']);
    Route::post('/proyectos',                          [ProyectoController::class, 'store']);
    Route::get('/proyectos/todos',                     [ProyectoController::class, 'todos']);
    Route::patch('/proyectos/{proyecto}/estado',       [ProyectoController::class, 'cambiarEstado']);
    Route::get('/proyectos/aprobados',                 [ProyectoController::class, 'aprobados']);
    Route::get('/proyectos/mis-asignados',             [ProyectoController::class, 'misAsignados']);
    Route::patch('/proyectos/{proyecto}/asignar-docente', [ProyectoController::class, 'asignarDocente']);
    Route::get('/proyectos/{proyecto}/entregas',          [ProyectoController::class, 'indexEntregas']);
    Route::post('/proyectos/{proyecto}/entregas',         [ProyectoController::class, 'storeEntrega']);
    Route::get('/proyectos/{proyecto}/documentos',        [DocumentoController::class, 'index']);
    Route::post('/proyectos/{proyecto}/documentos',       [DocumentoController::class, 'store']);
    Route::get('/documentos/{documento}/download',        [DocumentoController::class, 'download']);
    Route::delete('/documentos/{documento}',              [DocumentoController::class, 'destroy']);

    // Admin — Mentores
    Route::get('/mentors',          [AdminController::class, 'mentors']);
    Route::post('/mentors',         [AdminController::class, 'createMentor']);
    Route::delete('/mentors/{mentor}', [AdminController::class, 'deleteMentor']);

    // Admin — Asignaciones
    Route::get('/asignaciones',                [AsignacionController::class, 'index']);
    Route::post('/asignaciones',               [AsignacionController::class, 'store']);
    Route::delete('/asignaciones/{asignacion}', [AsignacionController::class, 'destroy']);

    // Mentor — Perfil
    Route::get('/mentor/perfil',  [MentorController::class, 'perfil']);
    Route::put('/mentor/perfil',  [MentorController::class, 'actualizarPerfil']);

    // Emprendedor — Perfil
    Route::get('/emprendedor/perfil', [EmprendedorController::class, 'perfil']);
    Route::put('/emprendedor/perfil', [EmprendedorController::class, 'actualizarPerfil']);

    // Seguimientos
    Route::post('/seguimientos/iniciar',              [SeguimientoController::class, 'iniciar']);
    Route::post('/seguimientos/avanzar',              [SeguimientoController::class, 'avanzar']);
    Route::get('/seguimientos/proyecto/{id_proyecto}',[SeguimientoController::class, 'porProyecto']);
    Route::get('/seguimientos/mis-mentorias',                      [SeguimientoController::class, 'misMentorias']);
    Route::get('/seguimientos/{seguimiento}/revisiones',           [RevisionController::class, 'index']);
    Route::post('/seguimientos/{seguimiento}/revisiones',          [RevisionController::class, 'store']);
    Route::patch('/revisiones/{revision}/observaciones',           [RevisionController::class, 'guardarObservaciones']);

    // Asesorías
    Route::get('/seguimientos/{seguimiento}/asesorias',    [AsesoriaController::class, 'index']);
    Route::post('/seguimientos/{seguimiento}/asesorias',   [AsesoriaController::class, 'store']);
    Route::put('/asesorias/{asesoria}',                    [AsesoriaController::class, 'update']);
    Route::delete('/asesorias/{asesoria}',                 [AsesoriaController::class, 'destroy']);

    // Admin — Mentores activos (selector)
    Route::get('/mentores-activos', [AdminController::class, 'mentoresActivos']);

    // Admin — Usuarios
    Route::get('/usuarios',                            [AdminController::class, 'usuarios']);
    Route::post('/usuarios',                           [AdminController::class, 'crearUsuario']);
    Route::put('/usuarios/{usuario}',                  [AdminController::class, 'editarUsuario']);
    Route::delete('/usuarios/{usuario}',               [AdminController::class, 'eliminarUsuario']);
    Route::patch('/usuarios/{usuario}/toggle-estado',  [AdminController::class, 'toggleEstado']);

    // Notificaciones
    Route::get('/notificaciones',                          [NotificacionController::class, 'index']);
    Route::patch('/notificaciones/{notificacion}/leer',    [NotificacionController::class, 'marcarLeida']);
    Route::patch('/notificaciones/leer-todas',             [NotificacionController::class, 'marcarTodas']);
    Route::delete('/notificaciones/leidas',                [NotificacionController::class, 'eliminarLeidas']);
});
