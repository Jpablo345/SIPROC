-- Se ejecuta una sola vez, cuando el volumen de PostgreSQL está vacío.
-- Base aparte para los tests (tests/TestCase.php exige que el nombre termine en _test).
CREATE DATABASE siproc_test;
