# La empresa: Aleación Prima

> Planta **inventada** para el proyecto. Es la única fuente de verdad sobre áreas, productos, recetas, insumos, personas y lotes de ejemplo: `DatabaseSeeder`, la demo y el resto de `docs/` cuentan esta misma historia. Encaja con [modelo-datos.md](./modelo-datos.md) y [flujo-lote.md](./flujo-lote.md); lo que este documento le pide al modelo está en la sección 9.
>
> Moneda: pesos mexicanos (MXN). Horas en tiempo local de Torreón (`America/Chihuahua`); el seeder las guarda en UTC.

## 1. Quién es

**Aleación Prima, S.A. de C.V.** Fundición de **hierro gris** (grafito laminar) y **hierro nodular** (grafito esferoidal) en Torreón, Coahuila. Fabrica piezas en bruto (sin maquinar) para refaccionarias de autopartes, fabricantes de maquinaria agrícola y talleres de la Comarca Lagunera: discos de freno, poleas, cajas de transmisión, soportes.

- Un turno (07:00–17:00, lunes a viernes), unas 20 personas.
- Un horno de inducción de media frecuencia de **750 kg** por carga, línea de moldeo en arena verde, granalladora, laboratorio con durómetro Brinell, espectrómetro y equipo de ultrasonido.
- Sus clientes son empresas; no vende a público en general.

**Cómo trabaja hoy.** La producción se lleva en el _cuaderno de colada_ del jefe de fusión, hojas de moldeo por caja y una hoja de Excel que Ventas actualiza cuando puede. Los imprevistos se cuentan de palabra o por WhatsApp. Nadie sabe cuánto metal regresa al horno por lote, cuántas piezas se rechazan en cada área ni cuánto costó realmente una pieza; el inventario de producto terminado de Ventas casi nunca coincide con el del almacén. Ese es el problema que resuelve el sistema.

## 2. Reglas de la planta

- **Un lote = una colada.** Un lote es de un solo producto y su metal cargado debe caber en el horno: `cantidad_objetivo × metal por pieza ≤ 750 kg`.
- **Qué es una "pieza" en cada parada.** En `MOL`, `FUS` y `COL` las piezas son **moldes** (un molde por pieza, así que `piezas_recibidas = cantidad_objetivo`). A partir de `DES` son piezas físicas.
- **Rendimiento.** El sistema de alimentación (canales, mazarotas) rinde 75 %: por cada kg de pieza se cargan `1 ÷ 0.75 × 1.02 ≈ 1.36 kg` de metal, donde el 2 % es la pérdida de horno (oxidación y escoria). Lo que no es pieza ni pérdida son **retornos**: vuelven al inventario como chatarra de fundición (ver sección 7).
- **Carga metálica** (porcentaje del metal cargado): 48 % chatarra de fundición, 48 % chatarra de acero 1020, 2.5 % recarburante, 1.5 % ferrosilicio. Los nodulares llevan además **2 % de nodulizante** en la cuchara.
- **Arena verde.** La mezcla es 80 % reciclada; se consume aproximadamente 1.4 kg de mezcla por kg de pieza.
- **Captura tardía.** Umbral de 30 minutos entre `ocurrido_en` y `registrado_en`.

## 3. Áreas (paradas) y tarifas

| Código | Área                           | MO ($/h) | Máquina ($/h) | Equipo                                               |
| :----: | ------------------------------ | -------: | ------------: | ---------------------------------------------------- |
| `MOL`  | Moldeo y preparación de arenas |      180 |           220 | Mezcladora de arena verde, atacador, línea de cajas  |
| `FUS`  | Fusión y ajuste metalúrgico    |      220 |           450 | Horno de inducción 750 kg, báscula de carga          |
| `COL`  | Colada y enfriamiento          |       60 |            80 | Cuchara de colada, inoculación en cuchara            |
| `DES`  | Desmolde                       |      160 |           180 | Desmolde vibratorio, separación de piezas y retornos |
| `GRA`  | Granallado y rebabeo           |      170 |           200 | Granalladora de esfera de acero, esmeriles           |
| `CAL`  | Laboratorio de calidad         |      250 |           300 | Durómetro Brinell, espectrómetro, ultrasonido        |
| `EMP`  | Empaque y expedición           |      150 |            40 | Paletizado, etiquetado de contenedor                 |

Notas:

- La tarifa de `COL` es baja a propósito: la mayor parte de sus horas es enfriamiento en caja, sin operador.
- `EMP` está **activa pero no entra en ningún recorrido por defecto**. Existe para demostrar que se puede _agregar_ una parada al crear un lote (así como la carcasa demuestra _quitar_ una).
- Tarifas por hora del área completa (su cuadrilla y su equipo), no por persona. Solo el administrador las edita; se congelan en `lote_areas` al crear el lote.

## 4. Productos

Recetas por pieza calculadas con las reglas de la sección 2. `metal` = suma de los cuatro insumos metálicos; `retorno esperado` = `metal × 0.98 − peso`.

| Código      | Producto                     | Material | Peso (kg) | Metal (kg) | Retorno esp. (kg) | Recorrido por defecto             | Mayoreo | Menudeo |
| ----------- | ---------------------------- | -------- | --------: | ---------: | ----------------: | --------------------------------- | ------: | ------: |
| `DIS-FRE-G` | Disco de freno               | Gris     |       8.5 |      11.60 |               2.9 | MOL → FUS → COL → DES → GRA → CAL |     280 |     340 |
| `POL-IND-G` | Polea industrial             | Gris     |       4.2 |       5.70 |               1.4 | MOL → FUS → COL → DES → GRA → CAL |     170 |     210 |
| `CAJ-TRA-N` | Caja de transmisión          | Nodular  |      14.0 |      19.00 |               4.6 | MOL → FUS → COL → DES → GRA → CAL |     650 |     780 |
| `CAR-PRE-N` | Carcasa de precisión         | Nodular  |       6.5 |       8.80 |               2.1 | MOL → FUS → COL → DES → CAL       |     420 |     500 |
| `SOP-MAN-N` | Soporte de mangueta agrícola | Nodular  |      11.5 |      15.60 |               3.8 | MOL → FUS → COL → DES → GRA → CAL |     540 |     650 |

Los precios **nunca** salen por la API pública ni por el canal `catalogo`. Los cinco productos están `publicado = true`.

### Recetas (`producto_insumo`, kg por pieza)

| Insumo                           | `DIS-FRE-G` | `POL-IND-G` | `CAJ-TRA-N` | `CAR-PRE-N` | `SOP-MAN-N` |
| -------------------------------- | ----------: | ----------: | ----------: | ----------: | ----------: |
| `CHT-FUN` Chatarra de fundición  |        5.57 |        2.74 |        9.12 |        4.22 |        7.49 |
| `CHT-102` Chatarra de acero 1020 |        5.57 |        2.74 |        9.12 |        4.22 |        7.49 |
| `REC-GRA` Recarburante           |        0.29 |        0.14 |        0.48 |        0.22 |        0.39 |
| `FES-75` Ferrosilicio            |        0.17 |        0.08 |        0.28 |        0.14 |        0.23 |
| `NOD-FSM` Nodulizante            |           — |           — |        0.38 |        0.18 |        0.31 |
| `ARE-VER` Arena verde (mezcla)   |       12.00 |        6.00 |       20.00 |       10.00 |       16.00 |

### Especificaciones (`productos.especificaciones`, lo que muestra el sitio)

- `DIS-FRE-G`: hierro gris clase 30 (ASTM A48), 8.5 kg en bruto, Ø 300 mm, para maquinado por el cliente.
- `POL-IND-G`: hierro gris clase 30, 4.2 kg, Ø 250 mm, 2 canales tipo B.
- `CAJ-TRA-N`: hierro nodular 65-45-12 (ASTM A536), 14 kg, con núcleo de arena resina.
- `CAR-PRE-N`: hierro nodular 65-45-12, 6.5 kg, tolerancias cerradas en asientos de rodamiento; **no se granalla** para no dañar el acabado antes del ultrasonido.
- `SOP-MAN-N`: hierro nodular 80-55-06, 11.5 kg, servicio pesado para tractor.

## 5. Insumos

| Código    | Insumo                             | Unidad | Costo ($/u) | Stock mínimo | Entrada inicial | Proveedor                    | Nº de colada / lote |
| --------- | ---------------------------------- | :----: | ----------: | -----------: | --------------: | ---------------------------- | ------------------- |
| `CHT-FUN` | Chatarra de fundición seleccionada |   kg   |        7.00 |        5,000 |          12,000 | Recicladora Laguna           | —                   |
| `CHT-102` | Chatarra de acero SAE 1020         |   kg   |        9.50 |        4,000 |           9,000 | Aceros del Norte             | `H-26-0412`         |
| `REC-GRA` | Recarburante de grafito            |   kg   |       30.00 |          150 |             600 | Minerales Industriales MX    | `RG-2607`           |
| `FES-75`  | Ferrosilicio FeSi 75               |   kg   |       40.00 |          150 |             400 | Ferroaleaciones de Monterrey | `FS-26-118`         |
| `NOD-FSM` | Nodulizante FeSiMg 5 %             |   kg   |       80.00 |          100 |             250 | Ferroaleaciones de Monterrey | `NM-26-031`         |
| `ARE-VER` | Arena verde (mezcla)               |   kg   |        1.20 |       10,000 |          25,000 | Preparación interna          | —                   |
| `CAJ-EMP` | Caja de despacho                   | pieza  |       45.00 |          100 |             300 | Empaques Torreón             | —                   |

Las entradas iniciales son `movimientos_insumo` de tipo `entrada` con fecha 2026-09-21 08:00, registradas por el administrador. La chatarra no trae número de colada; los demás sí (lote del proveedor). `insumos.existencia` **no se siembra a mano**: es la suma de movimientos.

## 6. Personas

| Rol             | Nombre          | Correo                      | Áreas (`area_user`) |
| --------------- | --------------- | --------------------------- | ------------------- |
| `administrador` | Humberto Castro | `humberto@aleacionprima.mx` | —                   |
| `ventas`        | Paloma Portillo | `paloma@aleacionprima.mx`   | —                   |
| `encargado`     | Julio López     | `julio@aleacionprima.mx`    | `MOL`, `COL`        |
| `encargado`     | Cesar Morillon  | `cesar@aleacionprima.mx`    | `FUS`               |
| `encargado`     | Roberto Gómez   | `roberto@aleacionprima.mx`  | `DES`, `GRA`        |
| `encargado`     | Ana Martínez    | `ana@aleacionprima.mx`      | `CAL`, `EMP`        |

Contraseña de todos en desarrollo: `password`. Todos `activo = true`.

### Clientes (recortable)

| Razón social                                    | RFC            | Contacto                                            | Condiciones |
| ----------------------------------------------- | -------------- | --------------------------------------------------- | ----------- |
| AutoPartes del Norte, S.A. de C.V.              | `APN851012HG4` | Ing. Ricardo Treviño, `rtrevino@autopartesnorte.mx` | 30 días     |
| AgroMaquinaria de la Laguna, S. de R.L. de C.V. | `AML980315KL2` | Lic. Elena Salgado, `esalgado@agrolaguna.mx`        | Contado     |

Ninguno tiene acceso al panel (`user_id = null`). Si pedidos entra al MVP: un pedido `confirmado` de 50 discos para AutoPartes (precio congelado 280) y uno `borrador` de 20 cajas para AgroMaquinaria; el lote 1 se liga al primero.

## 7. Retornos y mermas (decisión propuesta)

En fundición, entre 15 % y 25 % del metal colado no es pieza: son canales, mazarotas y piezas rotas al desmoldar. **No es merma:** vuelve al horno como chatarra de fundición. Si se contara como merma, el costo por pieza quedaría inflado y el sistema estaría midiendo mal justo lo que promete medir.

Propuesta (pendiente de validar con el equipo junto con el modelo):

- En la salida de `DES` el encargado captura `retorno_kg` (canales + mazarotas + piezas rotas).
- Eso genera un `movimientos_insumo` de tipo **`retorno`** (positivo, con `lote_id`) sobre `CHT-FUN` a su costo vigente.
- El costo de material del lote resta ese retorno: `material = Σ(asignado − devuelto) × costo − retorno_kg × costo(CHT-FUN)`.
- Las piezas rechazadas en `GRA` o `CAL` sí son merma del lote (`merma_piezas`); físicamente regresan al horno después, mediante una `entrada` manual de chatarra, fuera del costo del lote.

La pérdida de horno (2 %) no se captura: ya está dentro de la receta.

## 8. Lotes de ejemplo

Los tres se siembran con sus `lote_areas` completas. `cantidad_objetivo` × metal por pieza cabe en el horno en los tres casos.

### Lote 1 · `LOT-20260924-01` · recorrido completo, `terminado`

Disco de freno `DIS-FRE-G`, 50 piezas (580 kg de metal). Creado por Paloma el jueves 24 de septiembre a las 06:45.

Materia prima asignada (`lote_insumos`): `CHT-FUN` 278.5 kg · `CHT-102` 278.5 kg · `REC-GRA` 14.5 kg · `FES-75` 8.5 kg · `ARE-VER` 600 kg. Costo asignado: **$6,090.25**.

| Orden | Área  | Entrada (ocurrido) | Salida (ocurrido) | Horas | Recibidas | Entregadas | Merma | Quién   | Nota                                             |
| :---: | :---: | ------------------ | ----------------- | ----: | --------: | ---------: | ----: | ------- | ------------------------------------------------ |
|   1   | `MOL` | 24 sep 07:00       | 24 sep 10:30      |   3.5 |        50 |         50 |     0 | Julio   |                                                  |
|   2   | `FUS` | 24 sep 10:30       | 24 sep 12:30      |   2.0 |        50 |         50 |     0 | Cesar   | Temperatura de colada 1,450 °C                   |
|   3   | `COL` | 24 sep 12:30       | 24 sep 17:00      |   4.5 |        50 |         50 |     0 | Julio   | Enfriamiento en caja                             |
|   4   | `DES` | 25 sep 07:00       | 25 sep 08:30      |   1.5 |        50 |         48 |     2 | Roberto | 2 piezas rotas al desmoldar · **retorno 162 kg** |
|   5   | `GRA` | 25 sep 08:30       | 25 sep 10:30      |   2.0 |        48 |         47 |     1 | Roberto | 1 rechazada: rechupe visible en cara de fricción |
|   6   | `CAL` | 25 sep 10:30       | 25 sep 12:00      |   1.5 |        47 |         46 |     1 | Ana     | 1 rechazada: dureza fuera de rango (250 HB)      |

Todas las capturas dentro del umbral (`registrado_en` = `ocurrido_en` + 3 a 8 min). Retorno = 50 × 2.9 kg de canales y mazarotas + 2 piezas × 8.5 kg = 162 kg.

Pasa a `por_confirmar` el 25 sep 12:00; **Humberto confirma el 25 sep a las 13:15**. `piezas_terminadas = 46`; `movimientos_producto` de tipo `produccion` por 46.

| Componente          | Cálculo                                              |           MXN |
| ------------------- | ---------------------------------------------------- | ------------: |
| Material asignado   | ver arriba                                           |      6,090.25 |
| Retorno             | 162 kg × $7.00                                       |     −1,134.00 |
| **Material neto**   |                                                      |  **4,956.25** |
| Mano de obra        | 3.5×180 + 2×220 + 4.5×60 + 1.5×160 + 2×170 + 1.5×250 |      2,295.00 |
| Máquina             | 3.5×220 + 2×450 + 4.5×80 + 1.5×180 + 2×200 + 1.5×300 |      3,150.00 |
| **Costo real**      |                                                      | **10,401.25** |
| **Costo por pieza** | 10,401.25 ÷ 46                                       |    **226.11** |

Contra $280 de mayoreo deja 19 % de margen. Sin modelar el retorno el costo por pieza sería $250.77: esa diferencia es el argumento de la sección 7.

### Lote 2 · `LOT-20260928-01` · `en_proceso` en `DES`, con imprevisto y captura tardía

Caja de transmisión `CAJ-TRA-N`, 20 piezas (380 kg de metal). Creado por Paloma el lunes 28 de septiembre a las 06:50.

Materia prima asignada: `CHT-FUN` 182.4 kg · `CHT-102` 182.4 kg · `REC-GRA` 9.6 kg · `FES-75` 5.6 kg · `NOD-FSM` 7.6 kg · `ARE-VER` 400 kg. Costo asignado: **$4,609.60**.

| Orden | Área  | Entrada (ocurrido) | Salida (ocurrido) | Salida (registrado) | Horas | Recibidas | Entregadas | Quién   |
| :---: | :---: | ------------------ | ----------------- | ------------------- | ----: | --------: | ---------: | ------- |
|   1   | `MOL` | 28 sep 07:00       | 28 sep 10:00      | 28 sep 10:04        |   3.0 |        20 |         20 | Julio   |
|   2   | `FUS` | 28 sep 10:00       | 28 sep 12:30      | **28 sep 13:15**    |   2.5 |        20 |         20 | Cesar   |
|   3   | `COL` | 28 sep 12:30       | 28 sep 17:30      | 28 sep 17:35        |   5.0 |        20 |         20 | Julio   |
|   4   | `DES` | 29 sep 07:00       | —                 | —                   |     — |        20 |          — | Roberto |

- **Imprevisto** (Mongo, `imprevistos`): `tipo: falla_maquina`, área `FUS`, `maquina: "Horno de inducción 750 kg"`, `duracion_minutos: 40`, `resuelto: true`, ocurrido 28 sep 11:20, registrado 11:32. Descripción: "Caída de tensión; el horno perdió potencia 40 min y el baño bajó a 1,380 °C. Se recuperó a 1,480 °C antes de colar." Genera notificación al administrador.
- **Captura tardía**: la salida de `FUS` ocurrió a las 12:30 y se registró a las 13:15 (45 min > 30). La bitácora la marca `captura_tardia: true`.
- Estado: `en_proceso`, `area_actual_id = DES`. Retorno esperado al salir de `DES`: 92 kg.
- **Costo a la fecha** (paradas cerradas): material 4,609.60 + mano de obra 1,390.00 (3×180 + 2.5×220 + 5×60) + máquina 2,185.00 (3×220 + 2.5×450 + 5×80) = **$8,184.60**.

### Lote 3 · `LOT-20260925-01` · `cancelado` en la primera parada

Polea industrial `POL-IND-G`, 30 piezas (171 kg de metal). Creado por Paloma el viernes 25 de septiembre a las 06:50.

Materia prima asignada: `CHT-FUN` 82.2 kg · `CHT-102` 82.2 kg · `REC-GRA` 4.2 kg · `FES-75` 2.4 kg · `ARE-VER` 180 kg. Costo asignado: **$1,794.30**.

- Entrada a `MOL` el 25 sep 07:00 (Julio), 30 moldes.
- **Imprevisto**: `tipo: otro`, área `MOL`, ocurrido 08:10. "La mezcla salió con 6.5 % de humedad; los moldes colapsan al retirar el modelo." `resuelto: false`.
- **Humberto cancela el lote a las 08:40.** Motivo: "Arena fuera de especificación; se rehace la mezcla y se reprograma el lote." Como el lote no salió de su primera parada, se permite devolución:

| Insumo    | Asignado | Devuelto | Consumido |
| --------- | -------: | -------: | --------: |
| `CHT-FUN` |     82.2 |     82.2 |         0 |
| `CHT-102` |     82.2 |     82.2 |         0 |
| `REC-GRA` |      4.2 |      4.2 |         0 |
| `FES-75`  |      2.4 |      2.4 |         0 |
| `ARE-VER` |      180 |        0 |       180 |

Cada devolución es un `movimientos_insumo` de tipo `devolucion`. La parada abierta se cierra con `salida_ocurrido_en = cancelado_en` y `piezas_entregadas = 0` (1.67 h en `MOL`).

**Costo del lote cancelado** (se conserva, es la pérdida real): arena 180 × 1.20 = 216.00 + mano de obra 1.67 × 180 = 300.00 + máquina 1.67 × 220 = 366.67 = **$882.67**.

### Inventario inicial de producto terminado

Para que el sitio público muestre disponibilidad desde el primer día, `movimientos_producto` de tipo `ajuste` con motivo "Inventario inicial" (2026-09-21, administrador): `POL-IND-G` 120 · `CAJ-TRA-N` 15 · `SOP-MAN-N` 40 · `CAR-PRE-N` 0. `DIS-FRE-G` queda en 46 por la producción del lote 1.

## 9. Lo que este documento le pide al modelo

Para resolver al refinar [modelo-datos.md](./modelo-datos.md):

1. **`lote_areas.retorno_kg`** (decimal, null) y **`movimientos_insumo.tipo = retorno`**, más su resta en `CalculadorCostoLote` (sección 7).
2. **Cancelación cierra la parada abierta**: `salida_ocurrido_en = cancelado_en`, `piezas_entregadas = 0`, para que las horas cuenten en el costo sin regla especial.
3. **`lote_proveedor` nulo es normal** para chatarra; solo ferroaleaciones y acero de proveedor lo traen.
4. La merma de material (`merma_material`) queda sin usar en fundición: la merma es en piezas o es retorno. Argumento a favor de dejar `merma_material` opcional en vez de crear `lote_area_mermas`.
5. `piezas_recibidas` en las tres primeras paradas son moldes (sección 2); es solo una convención, no cambia columnas.

## 10. Fuera de la historia

La empresa no tiene, y el sistema no modela: maquinado, tratamiento térmico, ventas a público, proveedores como catálogo, mantenimiento de modelos y placas, certificados de calidad al cliente, reclamos de campo ni envíos. Todo eso existe en fundiciones reales y se menciona aquí solo para que nadie lo eche de menos.
