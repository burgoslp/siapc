import 'odontogram';

function convertirAFormatoVisual(datos) {
    const resultado = {};
    const superficies = ['vestibular', 'distal', 'palatine', 'mesial', 'occlusal'];

    for (const diente in datos) {
        const d = datos[diente];
        resultado[diente] = {};

        if (d.general && d.general !== 'SANO') {
            superficies.forEach(sup => {
                resultado[diente][sup] = true;
            });
        } else {
            const mapNombres = {
                'vestibular': 'vestibular',
                'mesial': 'mesial',
                'distal': 'distal',
                'lingual': 'palatine',
                'oclusal': 'occlusal',
            };

            for (const sup in d.superficies) {
                const nombreVisual = mapNombres[sup] || sup;
                resultado[diente][nombreVisual] = d.superficies[sup] !== 'SANO';
            }
        }
    }

    return resultado;
}

async function crearOdontograma(elementId, datos = null) {
    const pacienteId = elementId.replace('odontograma-', '');
    const contenedor = document.getElementById(`odontograma-container-${pacienteId}`);

    if (!contenedor) {
        console.error('Contenedor no encontrado para:', elementId);
        return null;
    }

    // Limpiar contenedor
    contenedor.innerHTML = '';

    // Crear el nuevo
    const nuevo = document.createElement('og-odontogram');
    nuevo.id = elementId;
    nuevo.setAttribute('mode', 'adult');
    nuevo.setAttribute('notation', 'fdi');
    nuevo.style.width = '100%';
    nuevo.style.display = 'block';
    nuevo.style.minHeight = '200px';

    contenedor.appendChild(nuevo);

    // Esperar a que el custom element esté definido y luego asignar datos
    if (datos && Object.keys(datos).length > 0) {
        const visual = convertirAFormatoVisual(datos);

        try {
            await customElements.whenDefined('og-odontogram');

            // Esperar un tick para que Lit hidrate
            requestAnimationFrame(() => {
                nuevo.chartData = visual;
                if (nuevo.requestUpdate) {
                    nuevo.requestUpdate();
                }
            });
        } catch (e) {
            console.warn('Custom element no definido, usando fallback timeout', e);
            setTimeout(() => {
                nuevo.chartData = visual;
                if (nuevo.requestUpdate) nuevo.requestUpdate();
            }, 100);
        }
    }

    return nuevo;
}

window.initOdontograma = function (elementId, datosIniciales = null) {
    crearOdontograma(elementId, datosIniciales);
};

window.refrescarOdontograma = function (elementId, datos) {
    crearOdontograma(elementId, datos);
};