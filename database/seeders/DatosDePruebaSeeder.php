<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Datos de prueba con aspecto real para revisar la interfaz: instituciones, grupos,
 * investigadores, revistas, artículos, libros, tesis y eventos entre 2016 y el año actual.
 *
 * Solo agrega filas: no borra ni modifica lo que ya existe, y se puede correr varias veces
 * sin duplicar (cada registro se busca por su clave natural antes de crearlo).
 *
 *   php artisan db:seed --class=DatosDePruebaSeeder
 *
 * No lo corras en la base de producción.
 */
class DatosDePruebaSeeder extends Seeder
{
    private ?int $autor = null;

    private Carbon $ahora;

    public function run(): void
    {
        mt_srand(2026);
        $this->ahora = now();
        $this->autor = User::role('Administrador')->value('id') ?? User::query()->value('id');

        DB::transaction(function () {
            $instituciones = $this->instituciones();
            $grupos = $this->grupos($instituciones['UFPSO']);
            $investigadores = $this->investigadores($grupos);
            $revistas = $this->revistas();
            $tiposLibro = $this->tiposLibro();
            $tipos = $this->tiposPublicacion();

            $this->articulos($tipos['articulo'], $revistas, $investigadores);
            $this->libros($tipos['libro'], $tiposLibro, $investigadores);
            if ($tipos['tesis']) {
                $this->tesis($tipos['tesis'], $investigadores);
            }
            $this->eventos($instituciones, $investigadores);
        });

        $this->command?->info('Datos de prueba cargados.');
    }

    /** @return array<string, int> nombre corto => institution_id */
    private function instituciones(): array
    {
        $lista = [
            'UFPSO' => ['UFPSO', 'Colombia', 'Ocaña', 'Publica', null],
            'UFPS' => ['Universidad Francisco de Paula Santander', 'Colombia', 'Cúcuta', 'Publica', 'https://ww2.ufps.edu.co'],
            'UIS' => ['Universidad Industrial de Santander', 'Colombia', 'Bucaramanga', 'Publica', 'https://www.uis.edu.co'],
            'UNAL' => ['Universidad Nacional de Colombia', 'Colombia', 'Bogotá', 'Publica', 'https://unal.edu.co'],
            'UNIPAMPLONA' => ['Universidad de Pamplona', 'Colombia', 'Pamplona', 'Publica', 'https://www.unipamplona.edu.co'],
            'UDEA' => ['Universidad de Antioquia', 'Colombia', 'Medellín', 'Publica', 'https://www.udea.edu.co'],
            'UNINORTE' => ['Universidad del Norte', 'Colombia', 'Barranquilla', 'Privada', 'https://www.uninorte.edu.co'],
            'UPM' => ['Universidad Politécnica de Madrid', 'España', 'Madrid', 'Publica', 'https://www.upm.es'],
            'UNAM' => ['Universidad Nacional Autónoma de México', 'México', 'Ciudad de México', 'Publica', 'https://www.unam.mx'],
            'ULA' => ['Universidad de Los Andes (Venezuela)', 'Venezuela', 'Mérida', 'Publica', null],
        ];

        $ids = [];
        foreach ($lista as $clave => [$nombre, $pais, $ciudad, $tipo, $web]) {
            $ids[$clave] = $this->buscarOCrear('institution', 'institution_id', ['institution_name' => $nombre], [
                'country' => $pais,
                'city' => $ciudad,
                'institution_type' => $tipo,
                'website' => $web,
            ]);
        }

        return $ids;
    }

    /** @return array<string, string> código => área temática */
    private function grupos(int $ufpso): array
    {
        $lista = [
            'COL0091234' => ['Ingeniería de Software y Sistemas de Información', 'Categoría A1', 'software'],
            'COL0092245' => ['Energía, Materiales y Sostenibilidad', 'Categoría A', 'energia'],
            'COL0093356' => ['Producción Agropecuaria y Desarrollo Rural', 'Categoría B', 'agro'],
            'COL0094467' => ['Gestión, Emprendimiento y Desarrollo Regional', 'Categoría B', 'gestion'],
            'COL0095578' => ['Pedagogía, Didáctica y Educación Inclusiva', 'Categoría C', 'educacion'],
            'COL0096689' => ['Infraestructura Civil y Recursos Hídricos', 'Categoría A', 'civil'],
        ];

        $areas = [];
        foreach ($lista as $codigo => [$nombre, $clasificacion, $area]) {
            $this->buscarOCrear('research_group', 'cod_minciencias', ['cod_minciencias' => $codigo], [
                'group_name' => $nombre,
                'group_classification' => $clasificacion,
                'institution_id' => $ufpso,
            ]);
            $areas[$codigo] = $area;
        }

        return $areas;
    }

    /**
     * @param  array<string, string>  $grupos
     * @return array<string, list<int>> área => researcher_id
     */
    private function investigadores(array $grupos): array
    {
        $nombres = [
            ['Laura', 'Marcela', 'Pacheco', 'Quintero'], ['Andrés', 'Felipe', 'Sánchez', 'Rojas'],
            ['Diana', 'Carolina', 'Ascanio', 'Pérez'], ['Jorge', 'Eliécer', 'Navarro', 'Torrado'],
            ['María', 'Fernanda', 'Arévalo', 'Bayona'], ['Carlos', 'Andrés', 'Galvis', 'Ortega'],
            ['Natalia', null, 'Ramírez', 'Claro'], ['Luis', 'Alberto', 'Pabón', 'Jácome'],
            ['Sandra', 'Milena', 'Angarita', 'Vergel'], ['Javier', null, 'Trillos', 'Durán'],
            ['Paola', 'Andrea', 'Carrascal', 'Lobo'], ['Óscar', 'Mauricio', 'Bayona', 'Sanjuan'],
            ['Yesenia', null, 'Quintero', 'Álvarez'], ['Héctor', 'Julio', 'Páez', 'Coronel'],
            ['Camila', 'Alejandra', 'Ovalle', 'Guerrero'], ['Fredy', 'Orlando', 'Peñaranda', 'Sepúlveda'],
            ['Adriana', 'Patricia', 'Contreras', 'Barbosa'], ['Edwin', null, 'Barrientos', 'Avendaño'],
            ['Liliana', 'Esther', 'Vega', 'Picón'], ['Ricardo', 'José', 'Lemus', 'Arenas'],
            ['Ángela', 'María', 'Carvajalino', 'Mora'], ['Daniel', 'Esteban', 'Rincón', 'Toscano'],
            ['Karen', 'Lizeth', 'Manzano', 'Prado'], ['Germán', null, 'Ballesteros', 'Clavijo'],
            ['Juliana', 'Isabel', 'Florez', 'Amaya'], ['Wilmer', 'Arley', 'Castro', 'Leal'],
            ['Silvia', 'Juliana', 'Roperto', 'Duarte'], ['Mauricio', null, 'Sarmiento', 'Velásquez'],
            ['Tatiana', 'Marcela', 'Gómez', 'Quintana'], ['Nelson', 'Enrique', 'Bastos', 'Uribe'],
        ];

        $codigos = array_keys($grupos);
        $porArea = [];

        foreach ($nombres as $i => [$n1, $n2, $a1, $a2]) {
            $codigo = $codigos[$i % count($codigos)];
            $documento = (string) (1091650000 + $i * 7919);
            $correo = $this->sinTildes(mb_strtolower($n1[0].$a1.($a2 ? mb_substr($a2, 0, 1) : ''))).'@ufpso.edu.co';

            $id = $this->buscarOCrear('researcher', 'researcher_id', ['document' => $documento], [
                'name_1' => $n1,
                'name_2' => $n2,
                'last_name_1' => $a1,
                'last_name_2' => $a2,
                'email' => $correo,
                'phone' => '31'.mt_rand(0, 9).' '.mt_rand(100, 999).' '.mt_rand(1000, 9999),
                'cod_minciencias' => $codigo,
            ]);

            $porArea[$grupos[$codigo]][] = $id;
        }

        return $porArea;
    }

    /** @return list<array{issn: string, area: string}> */
    private function revistas(): array
    {
        $lista = [
            ['0123-4501', 'Revista Colombiana de Computación Aplicada', 'Q3', 'software'],
            ['1692-7702', 'Ingeniería e Innovación Tecnológica', 'Q2', 'software'],
            ['2346-1803', 'Journal of Software Engineering Research', 'Q1', 'software'],
            ['0120-5604', 'Energía y Ambiente', 'Q2', 'energia'],
            ['1909-2405', 'Renewable Materials and Processes', 'Q1', 'energia'],
            ['0121-3706', 'Ciencia Agropecuaria del Trópico', 'Q3', 'agro'],
            ['2027-4307', 'Agronomía y Suelos', 'Q4', 'agro'],
            ['0120-3908', 'Revista de Administración y Desarrollo', 'Q4', 'gestion'],
            ['1900-6009', 'Estudios Gerenciales Regionales', 'Q3', 'gestion'],
            ['0123-1210', 'Educación y Pedagogía Latinoamericana', 'Q2', 'educacion'],
            ['2422-0011', 'Infraestructura y Recursos Hídricos', 'Q2', 'civil'],
            ['0122-8312', 'Revista Ingenio Regional', null, 'civil'],
        ];

        $revistas = [];
        foreach ($lista as [$issn, $nombre, $categoria, $area]) {
            $this->buscarOCrear('journal', 'journal_issn', ['journal_issn' => $issn], [
                'journal_name' => $nombre,
                'category' => $categoria,
            ]);
            $revistas[] = ['issn' => $issn, 'area' => $area];
        }

        return $revistas;
    }

    /** @return list<int> */
    private function tiposLibro(): array
    {
        return array_map(
            fn (string $nombre) => $this->buscarOCrear('book_type', 'book_type_id', ['type_name' => $nombre], []),
            ['Libro resultado de investigación', 'Capítulo de libro', 'Libro de texto'],
        );
    }

    /** Reutiliza los tipos que ya existen (Artículo, Libro, Tesis) sin importar mayúsculas o tildes. */
    private function tiposPublicacion(): array
    {
        $buscar = function (array $nombres): ?int {
            foreach (DB::table('publication_type')->get(['type_id', 'type_name']) as $tipo) {
                if (in_array($this->sinTildes(mb_strtolower($tipo->type_name)), $nombres, true)) {
                    return $tipo->type_id;
                }
            }

            return null;
        };

        $articulo = $buscar(['articulo', 'articulos', 'articulo cientifico'])
            ?? $this->buscarOCrear('publication_type', 'type_id', ['type_name' => 'Artículo'], []);
        $libro = $buscar(['libro', 'libros'])
            ?? $this->buscarOCrear('publication_type', 'type_id', ['type_name' => 'Libro'], []);

        return ['articulo' => $articulo, 'libro' => $libro, 'tesis' => $buscar(['tesis'])];
    }

    private function articulos(int $tipo, array $revistas, array $investigadores): void
    {
        $temas = [
            'software' => [
                'Arquitectura de microservicios para la gestión académica en universidades públicas',
                'Evaluación de la usabilidad de plataformas de aprendizaje virtual en estudiantes de ingeniería',
                'Modelo de madurez para pruebas automatizadas en pequeñas empresas de software',
                'Detección de noticias falsas en español mediante aprendizaje profundo',
                'Accesibilidad web en portales institucionales de educación superior en Colombia',
                'Clasificación de imágenes de cultivos con redes neuronales convolucionales',
                'Uso de metodologías ágiles en proyectos de software del sector público',
            ],
            'energia' => [
                'Potencial solar fotovoltaico en zonas rurales de Norte de Santander',
                'Caracterización de biocombustibles a partir de residuos de café',
                'Eficiencia energética en edificaciones universitarias de clima cálido',
                'Materiales compuestos reforzados con fibra de fique para aplicaciones estructurales',
                'Simulación de microrredes híbridas para comunidades no interconectadas',
            ],
            'agro' => [
                'Manejo integrado de plagas en cultivos de cebolla de bulbo en la provincia de Ocaña',
                'Calidad del suelo bajo sistemas silvopastoriles en el Catatumbo',
                'Rendimiento del frijol con abonos orgánicos fermentados',
                'Diagnóstico de la cadena productiva del cacao en Norte de Santander',
                'Efecto del riego por goteo en la producción de tomate bajo invernadero',
            ],
            'gestion' => [
                'Factores de éxito de los emprendimientos rurales en el nororiente colombiano',
                'Responsabilidad social empresarial en las pymes de Ocaña',
                'Transformación digital del comercio minorista después de la pandemia',
                'Gestión del talento humano en cooperativas agrícolas',
            ],
            'educacion' => [
                'Estrategias didácticas para la enseñanza de las matemáticas en la educación rural',
                'Inclusión de estudiantes con discapacidad visual en la educación superior',
                'Competencias digitales docentes en instituciones educativas públicas',
                'Deserción estudiantil en los primeros semestres de ingeniería',
            ],
            'civil' => [
                'Calidad del agua en la cuenca del río Algodonal',
                'Comportamiento de mezclas asfálticas con caucho reciclado',
                'Evaluación del riesgo por remoción en masa en vías terciarias',
                'Gestión de aguas lluvias con sistemas urbanos de drenaje sostenible',
            ],
        ];

        $paises = ['Colombia', 'Colombia', 'Colombia', 'España', 'México', 'Brasil', 'Chile', 'Estados Unidos'];
        $n = 0;

        foreach ($temas as $area => $titulos) {
            $deArea = array_values(array_filter($revistas, fn ($r) => $r['area'] === $area));
            foreach ($titulos as $titulo) {
                $anio = $this->anio();
                $internacional = mt_rand(0, 2) === 0;
                $pais = $internacional ? $paises[mt_rand(3, count($paises) - 1)] : 'Colombia';
                $revista = mt_rand(0, 4) === 0 ? $revistas[mt_rand(0, count($revistas) - 1)] : $deArea[mt_rand(0, count($deArea) - 1)];

                $id = $this->publicacion($titulo, $anio, $tipo, $internacional ? 'Internacional' : 'Nacional', $pais, null);
                if ($id === null) {
                    continue;
                }

                $n++;
                DB::table('article')->insert([
                    'publication_id' => $id,
                    'journal_issn' => $revista['issn'],
                    'doi' => mt_rand(0, 3) > 0 ? sprintf('10.%d/siproc.%d.%03d', 22000 + mt_rand(0, 900), $anio, $n) : null,
                ]);
                $this->autores($id, $investigadores[$area]);
            }
        }
    }

    private function libros(int $tipo, array $tiposLibro, array $investigadores): void
    {
        $lista = [
            ['Fundamentos de ingeniería de requisitos', 'software', 'Editorial UFPSO'],
            ['Programación orientada a objetos con ejemplos en Java', 'software', 'Ecoe Ediciones'],
            ['Energías renovables para el desarrollo rural', 'energia', 'Editorial UFPSO'],
            ['Agricultura sostenible en la provincia de Ocaña', 'agro', 'Editorial Universidad de Pamplona'],
            ['El cultivo de la cebolla: manejo agronómico y poscosecha', 'agro', 'Editorial UFPSO'],
            ['Emprendimiento e innovación en regiones de frontera', 'gestion', 'Alfaomega'],
            ['Didáctica de las ciencias en contextos rurales', 'educacion', 'Editorial Magisterio'],
            ['Hidrología aplicada a cuencas de montaña', 'civil', 'Editorial UFPSO'],
            ['Diseño de pavimentos para vías terciarias', 'civil', 'Ecoe Ediciones'],
            ['Inteligencia artificial: conceptos y aplicaciones', 'software', 'Alfaomega'],
        ];

        foreach ($lista as $i => [$titulo, $area, $editorial]) {
            $id = $this->publicacion($titulo, $this->anio(), $tipo, $i % 4 === 0 ? 'Internacional' : 'Nacional', 'Colombia', null);
            if ($id === null) {
                continue;
            }

            DB::table('book')->insert([
                'publication_id' => $id,
                'book_isbn' => sprintf('978-958-%04d-%02d-%d', 5000 + $i * 37, mt_rand(10, 99), mt_rand(0, 9)),
                'means_of_dissemination' => ['Impreso', 'Digital', 'Digital e impreso'][mt_rand(0, 2)],
                'editorial' => $editorial,
                'book_type_id' => $tiposLibro[$i % 3 === 2 ? 1 : 0],
            ]);
            $this->autores($id, $investigadores[$area], 3);
        }
    }

    private function tesis(int $tipo, array $investigadores): void
    {
        $lista = [
            ['Sistema de información para el seguimiento de egresados', 'software', 'Ingenieria de software'],
            ['Aplicación móvil para el reporte de daños en la malla vial', 'civil', 'Gestion de infraestructura'],
            ['Diseño de un secador solar para cacao', 'energia', 'Energia solar termica'],
            ['Plan de negocios para una asociación de productores de aguacate', 'gestion', 'Emprendimiento rural'],
            ['Uso de videojuegos educativos en la enseñanza de fracciones', 'educacion', 'Didactica de las matematicas'],
            ['Evaluación de bioinsumos en el cultivo de cebolla', 'agro', 'Agricultura sostenible'],
        ];

        foreach ($lista as [$titulo, $area, $tema]) {
            $id = $this->publicacion($titulo, $this->anio(2019), $tipo, 'Nacional', 'Colombia', ['tema' => $tema]);
            if ($id !== null) {
                $this->autores($id, $investigadores[$area], 2);
            }
        }
    }

    private function eventos(array $instituciones, array $investigadores): void
    {
        $lista = [
            ['Congreso Internacional de Ingeniería de Software', 'Internacional', 'UPM', 'software'],
            ['Encuentro Regional de Semilleros de Investigación', 'Nacional', 'UFPSO', 'educacion'],
            ['Simposio Colombiano de Energías Renovables', 'Nacional', 'UIS', 'energia'],
            ['Congreso Latinoamericano de Agronomía', 'Internacional', 'UNAM', 'agro'],
            ['Foro de Emprendimiento y Desarrollo Regional', 'Nacional', 'UFPS', 'gestion'],
            ['Jornadas de Ingeniería Civil y Ambiental', 'Nacional', 'UNAL', 'civil'],
            ['Conferencia Iberoamericana de Educación Inclusiva', 'Internacional', 'UNINORTE', 'educacion'],
            ['Semana de la Ciencia y la Tecnología UFPSO', 'Nacional', 'UFPSO', 'software'],
            ['Congreso Nacional de Computación', 'Nacional', 'UDEA', 'software'],
            ['Encuentro Binacional de Investigación Agraria', 'Internacional', 'ULA', 'agro'],
        ];

        $participaciones = ['Ponente', 'Ponente', 'Asistente', 'Organizador'];

        foreach ($lista as [$nombre, $alcance, $sede, $area]) {
            $anio = $this->anio(2018);
            $id = $this->buscarOCrear('event', 'event_id', ['event_name' => $nombre], [
                'event_year' => $anio,
                'event_month' => (string) mt_rand(1, 12),
                'event_scope' => $alcance,
                'event_url' => null,
                'host_institution_id' => $instituciones[$sede],
                'origin_institution_id' => $instituciones['UFPSO'],
            ]);

            foreach ($this->elegir($investigadores[$area], mt_rand(1, 3)) as $investigador) {
                $tipo = $participaciones[mt_rand(0, 3)];
                DB::table('researcher_event')->insertOrIgnore([
                    'event_id' => $id,
                    'researcher_id' => $investigador,
                    'participation_type' => $tipo,
                    'presentation_title' => $tipo === 'Ponente' ? 'Avances del proyecto de '.mb_strtolower($nombre) : null,
                ]);
            }
        }
    }

    /** Crea la publicación si no existe otra con el mismo título; devuelve null si ya existía. */
    private function publicacion(string $titulo, int $anio, int $tipo, string $alcance, string $pais, ?array $extra): ?int
    {
        if (DB::table('publication')->where('title', $titulo)->exists()) {
            return null;
        }

        // Fechas de registro repartidas en el último año para que la actividad reciente tenga movimiento.
        $registro = $this->ahora->copy()->subDays(mt_rand(0, 330))->subMinutes(mt_rand(0, 1400));

        return DB::table('publication')->insertGetId([
            'title' => $titulo,
            'publication_year' => $anio,
            'scope' => $alcance,
            'country_publication' => $pais,
            'url' => null,
            'type_id' => $tipo,
            'extra_attributes' => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
            'created_by' => $this->autor,
            'created_at' => $registro,
            'updated_at' => $registro,
        ], 'publication_id');
    }

    private function autores(int $publicacion, array $candidatos, int $maximo = 4): void
    {
        foreach ($this->elegir($candidatos, mt_rand(1, min($maximo, count($candidatos)))) as $orden => $investigador) {
            DB::table('researcher_publication')->insert([
                'publication_id' => $publicacion,
                'researcher_id' => $investigador,
                'author_order' => $orden + 1,
            ]);
        }
    }

    /** Más publicaciones en los años recientes, como en un grupo que va creciendo. */
    private function anio(int $desde = 2016): int
    {
        $actual = (int) $this->ahora->year;

        $atras = (int) floor((1 - sqrt(mt_rand(0, 1000) / 1000)) * ($actual - $desde + 1));

        return max($desde, $actual - $atras);
    }

    private function elegir(array $lista, int $cantidad): array
    {
        $copia = $lista;
        for ($i = count($copia) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$copia[$i], $copia[$j]] = [$copia[$j], $copia[$i]];
        }

        return array_slice($copia, 0, $cantidad);
    }

    private function buscarOCrear(string $tabla, string $clave, array $busqueda, array $valores): int|string
    {
        $existente = DB::table($tabla)->where($busqueda)->value($clave);
        if ($existente !== null) {
            return $existente;
        }

        $fila = $busqueda + $valores + ['created_by' => $this->autor, 'created_at' => $this->ahora, 'updated_at' => $this->ahora];

        if (isset($busqueda[$clave])) {
            DB::table($tabla)->insert($fila);

            return $busqueda[$clave];
        }

        return DB::table($tabla)->insertGetId($fila, $clave);
    }

    private function sinTildes(string $texto): string
    {
        return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);
    }
}
