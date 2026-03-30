# 🚀 Guía de Inicio del Proyecto

¡Te damos la bienvenida al proyecto! Este documento contiene las instrucciones necesarias para levantar el entorno de desarrollo local desde cero.

## 📋 Requisitos Previos

Para poder ejecutar este proyecto, tu sistema (está pensado para **Linux**) debe contar con las siguientes herramientas instaladas:

* **[Git](https://git-scm.com/)**
* **[Docker](https://docs.docker.com/engine/install/)**
* **[Docker Compose](https://docs.docker.com/compose/install/)**

---

## 🛠️ Instalación y Configuración Paso a Paso

Sigue estos pasos en orden para configurar tu entorno local:

### 1. Clonar el repositorio
Descarga el código fuente a tu máquina local y entra en el directorio del proyecto:

```bash
git clone <URL_DEL_REPOSITORIO>
cd <NOMBRE_DE_LA_CARPETA>
```

### 2. Configurar el archivo de hosts
Para que el entorno local resuelva correctamente las peticiones, necesitas añadir el dominio del proyecto a tu archivo `/etc/hosts`.

Abre el archivo con permisos de administrador:
```bash
sudo nano /etc/hosts
```
Añade la siguiente línea al final del archivo (cambia `midominio.local` por el dominio real que use el proyecto tal como aparece en el .env.dev):
```text
127.0.0.1    midominio.local
```

### 3. Configurar variables de entorno
Crea tu archivo de variables de entorno local a partir del archivo de desarrollo base:

```bash
cp .env.dev .env.local
```
> **Nota:** Revisa el archivo `.env.local` por si necesitas ajustar alguna credencial específica de tu máquina, aunque los valores por defecto deberían funcionar.

### 4. Levantar los contenedores
Inicia el entorno de Docker. El siguiente comando es un alias que se encarga de construir (`make build`) y levantar (`make up`) los contenedores en segundo plano:

```bash
make start
```

### 5. Inicializar la base de datos
Una vez que los contenedores estén corriendo, crea la base de datos y ejecuta las migraciones necesarias con un solo comando:

```bash
make init
```

### 6. Compilar e instalar assets
Para que la interfaz gráfica (CSS, JS, imágenes) funcione correctamente, compila e instala los assets del proyecto:

```bash
make develop
make asset-install
```

### 7. Cargar datos de prueba (Usuario Admin)
Para poder probar la plataforma desde la interfaz web, necesitas un usuario. Ejecuta el siguiente comando para cargar las *fixtures* (datos de prueba) en la base de datos, lo que generará un usuario administrador:

```bash
make sf c="doctrine:fixtures:load"
```
Esto genera un par de usuarios, de los que tenemos admin@dediez.software como administrador. Podemos darle a recuperar contraseña, abrir localhost:8025 para acceder a mailpit y completar el flujo de recuperación de contraseña.

### 8. Ejecutar los tests
Finalmente, asegúrate de que todo se ha configurado correctamente y el proyecto funciona como se espera ejecutando la batería de pruebas:

```bash
make test
```

---

¡Happy coding! 💻
