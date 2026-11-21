import { BarcodeScanner, BarcodeFormat } from '@capacitor-mlkit/barcode-scanning';

export const permisoCamara = async (): Promise<boolean> => {
  const { camera } = await BarcodeScanner.checkPermissions();
  if (camera === 'granted' || camera === 'limited') return true;
  if (camera === 'denied') return false;
  const respuesta = await BarcodeScanner.requestPermissions();
  return respuesta.camera === 'granted' || respuesta.camera === 'limited';
};

export const iniciarEscaneo = async (alLeer: (texto: string) => void): Promise<void> => {
  await BarcodeScanner.addListener('barcodesScanned', ({ barcodes }) => {
    const texto = barcodes[0]?.rawValue;
    if (texto) alLeer(texto);
  });
  await BarcodeScanner.startScan({ formats: [BarcodeFormat.QrCode] });
};

export const detenerEscaneo = async (): Promise<void> => {
  await BarcodeScanner.removeAllListeners();
  await BarcodeScanner.stopScan();
};
