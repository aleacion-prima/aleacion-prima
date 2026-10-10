# Glosario

> Las palabras que el sistema, `docs/`, el código y el anteproyecto usan con un significado preciso. Si un término aparece aquí, se usa siempre así; si un PR necesita uno nuevo, se agrega en el mismo PR. Fuentes: [empresa.md](./empresa.md), [flujo-lote.md](./flujo-lote.md), [modelo-datos.md](./modelo-datos.md).

## Fundición

**Hierro gris.** Fundición de hierro con el carbono en forma de grafito laminar. Barato, fácil de colar, absorbe vibración; frágil. Discos de freno y poleas. Norma ASTM A48, clase por resistencia (clase 30 = 30 ksi).

**Hierro nodular.** Hierro con el grafito en esferas, gracias al nodulizante. Más resistente y dúctil que el gris; cajas, carcasas y soportes. Norma ASTM A536, grado por resistencia-fluencia-elongación (65-45-12, 80-55-06).

**Horno de inducción.** Horno eléctrico que funde la carga metálica por corrientes inducidas. El de la planta admite **750 kg por carga**, y eso limita el tamaño de un lote (`planta.capacidad_colada_kg`).

**Carga metálica.** Lo que entra al horno: en la planta, 48 % chatarra de fundición, 48 % chatarra de acero 1020, 2.5 % recarburante, 1.5 % ferrosilicio. Los nodulares agregan 2 % de nodulizante en la cuchara. Es la parte `metal` de la receta.

**Chatarra de fundición** (`CHT-FUN`). Hierro fundido reciclado: retornos de coladas anteriores y piezas rechazadas. Es el insumo al que regresan los retornos (`planta.insumo_retorno`).

**Ferroaleaciones.** Insumos que ajustan la composición: **recarburante** (`REC-GRA`, grafito, sube el carbono), **ferrosilicio** (`FES-75`, sube el silicio) y **nodulizante** (`NOD-FSM`, ferrosilicio-magnesio, vuelve el grafito esferoidal). Los tres traen **lote del proveedor**.

**Arena verde** (`ARE-VER`). Mezcla de arena sílica, arcilla bentonita y agua con la que se hacen los moldes. "Verde" porque está húmeda, no por el color. 80 % reciclada; unos 1.4 kg de mezcla por kg de pieza. Es el insumo de categoría `moldeo`.

**Molde.** Bloque de arena compactada, en dos mitades dentro de una caja, con el hueco de la pieza y su sistema de alimentación. Un molde por pieza. En el sistema, las "piezas" de `MOL`, `FUS` y `COL` son moldes.

**Colada.** El vertido del metal fundido en los moldes. También la carga completa del horno vertida de una vez: **un lote = una colada**. Es el área `COL`. No confundir con _número de colada_ del proveedor (ver **lote del proveedor**).

**Cuchara.** Recipiente refractario con el que se lleva el metal del horno a los moldes. Ahí se hace la **inoculación** (adición de ferrosilicio justo antes de colar) y la nodulización.

**Sistema de alimentación.** Los conductos por donde entra el metal al molde: **canales** (o bebederos) y **mazarotas** (depósitos que alimentan la pieza mientras se contrae al solidificar). Son metal bueno que no es pieza.

**Rendimiento.** Fracción del metal colado que acaba siendo pieza. En la planta, 75 %: por cada kg de pieza se cargan `1 ÷ 0.75 × 1.02 ≈ 1.36 kg`.

**Pérdida de horno.** Metal que se pierde por oxidación y escoria al fundir, alrededor de 2 %. No se captura: ya va dentro de la receta.

**Retorno.** Metal que regresa al horno después de desmoldar: canales, mazarotas y piezas rotas. Entre 15 % y 25 % del metal colado. **No es merma:** se captura en kg en la salida de `DES` (`lote_areas.retorno_kg`), genera un `movimientos_insumo` de tipo `retorno` sobre `CHT-FUN` y **se resta** del costo de material (ADR-0012).

**Merma.** Piezas que se pierden en una parada: `merma_piezas = piezas_recibidas − piezas_entregadas`. Moldes que colapsan, piezas rotas al desmoldar, piezas rechazadas en granallado o calidad. La merma encarece cada pieza buena; no se cobra aparte. `merma_material` en kg existe pero es opcional (dec. 12 del modelo).

**Desmolde.** Sacar las piezas del molde una vez frías, normalmente en mesa vibratoria. Área `DES`. Es donde se separan piezas y retornos, y desde donde las "piezas" son piezas físicas.

**Granallado.** Limpieza de la pieza con chorro de esferas de acero (granalla) para quitar arena adherida y óxido. Área `GRA`, junto con el **rebabeo** (esmerilar rebabas y restos de canales). La carcasa de precisión no se granalla.

**Rechupe.** Hueco o hundimiento en la pieza por contracción del metal al solidificar, cuando la mazarota no alimentó bien. Causa típica de rechazo en `GRA`.

**Dureza Brinell (HB).** Prueba de dureza que hace el laboratorio (`CAL`). Fuera de rango = rechazo. El laboratorio también hace espectrometría (composición) y ultrasonido (defectos internos).

**Cuaderno de colada.** Libreta donde el jefe de fusión anota cada colada: fecha, carga, temperatura, observaciones. Es lo que hoy hace de "sistema" en la planta.

**Hojas de moldeo por caja.** Registro manual por caja de moldeo: cuántos moldes se hicieron, para qué pieza. La otra mitad del "sistema" actual.

## El sistema

**Lote.** Unidad de producción y de trazabilidad: un producto, una cantidad objetivo, un recorrido y una colada. Código `LOT-AAAAMMDD-NN`. Tabla `lotes`.

**Parada.** Cada paso de un lote por un área. Una fila de `lote_areas` con `orden`, entrada y salida. Un área puede aparecer dos veces en un lote (retrabajo) como dos paradas.

**Área.** Sección de la planta con su equipo, su cuadrilla y sus tarifas. Tabla `areas`. Códigos `MOL`, `FUS`, `COL`, `DES`, `GRA`, `CAL`, `EMP`.

**Área obligatoria / opcional.** `MOL → FUS → COL → DES` van en todo recorrido, en ese orden relativo; `GRA`, `CAL` y `EMP` van donde se pongan (ADR-0009, dec. 17). Columna `areas.obligatoria`.

**Recorrido.** La secuencia de paradas de un lote. Cada producto tiene uno **por defecto** (`producto_area`); al crear el lote se copia y se ajusta, y desde entonces vive en `lote_areas`. El componente que lo muestra es `RecorridoTimeline.vue` (modos editar, ajustar, ver).

**Receta.** Insumos por pieza de cada producto (`producto_insumo`). Es lo que permite asignar materia prima automáticamente al crear el lote.

**Encargado.** Persona responsable de capturar en una o varias áreas. Rol `encargado`; sus áreas en `area_user`.

**Piezas recibidas / entregadas.** Lo que entra y sale de una parada. Las recibidas de la parada N+1 se prellenan con las entregadas de la N; si difieren, la diferencia queda como discrepancia.

**Ocurrido / registrado.** Cada captura guarda dos horas: `ocurrido_en` (cuándo pasó, la pone el encargado, no puede ser futura) y `registrado_en` (cuándo se capturó, la pone el servidor). Las horas de la parada se calculan con `ocurrido_en`.

**Captura tardía.** Cuando `registrado_en − ocurrido_en` supera 30 minutos (`planta.umbral_captura_tardia`). Se permite y se marca en la bitácora; prohibirla solo produciría horas inventadas.

**Imprevisto.** Algo que pasó en una parada y no es piezas ni tiempo: `falla_maquina`, `retraso_material`, `pieza_defectuosa`, `otro`. Colección `imprevistos` en MongoDB; genera notificación. No modifica `lote_areas` (dec. 13).

**Bitácora.** Registro de todo lo que le pasa a un lote, en orden, inmutable. Colección `bitacora` en MongoDB, escrita después del commit de Postgres (ADR-0003).

**Tarifa congelada.** Las tarifas por hora del área (`tarifa_mano_obra_hora`, `costo_maquina_hora`) y el costo unitario de cada insumo se copian al lote al crearlo. Cambiar el catálogo después no toca lotes existentes.

**Costo a la fecha / costo real.** Mientras el lote está abierto, `costo_total` es la suma de las paradas cerradas; al confirmar se congela y se llama costo real. `costo_por_pieza = costo_total ÷ piezas_terminadas`. Solo `CalculadorCostoLote` escribe estas columnas.

**Por confirmar.** Estado del lote entre la salida de la última parada y la confirmación del administrador. Las piezas no cuentan en inventario hasta confirmar (ADR-0010).

**Confirmar.** Acción del administrador que cierra el lote: `terminado`, piezas al inventario de producto terminado, costo real congelado, evento `disponibilidad.actualizada` al sitio.

**Cancelar.** Cerrar un lote antes de terminar, con motivo. Devolución de insumos solo si no salió de la primera parada; la parada abierta se cierra con la hora de cancelación; el lote conserva su costo (es la pérdida real).

**Ajuste.** La única forma de corregir algo ya registrado: valor anterior, valor nuevo, motivo y responsable. Un lote terminado no se reabre (ADR-0011). Tabla `ajustes`, con arco exclusivo hacia `lote_areas`, `lote_insumos`, `insumos` o `productos`.

**Movimiento.** Cada cambio de inventario, con signo y responsable: `movimientos_insumo` (`entrada`, `asignacion`, `devolucion`, `retorno`, `ajuste`) y `movimientos_producto` (`produccion`, `venta`, `ajuste`). La **existencia** es su suma, guardada como caché.

**Lote del proveedor.** Número con el que el proveedor identifica una partida de material (en la industria, _heat number_ o número de colada del acero). Columna `lote_proveedor` en entradas y `lote_insumos`. La chatarra no lo trae. Se llama así y no `numero_colada` para no chocar con **colada**.

**Etiqueta.** Impresión con el código del lote en texto y en QR, producto, cantidad y fecha, que se pega al lote físico. El QR solo contiene el código (ADR-0004).

**Tablero.** Pantalla del administrador con todos los lotes en tiempo real, vía Reverb. En el tablero solo pulsa el lote seleccionado.
