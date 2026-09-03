import mysql from 'mysql2/promise';

let pool;

export function createDeviceRegistry() {
  if (!pool) {
    pool = mysql.createPool({
      host: process.env.DB_HOST || '127.0.0.1',
      port: Number(process.env.DB_PORT || 3306),
      user: process.env.DB_USERNAME,
      password: process.env.DB_PASSWORD,
      database: process.env.DB_DATABASE,
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0,
    });
  }

  return {
    async findByImei(imei) {
      const [rows] = await pool.execute(
        `SELECT id, imei, brand, model, codec FROM devices WHERE imei = ? LIMIT 1`,
        [imei]
      );

      return rows[0] || null;
    },

    async updateCodec(imei, codec) {
      await pool.execute(
        `UPDATE devices SET codec = ?, updated_at = NOW() WHERE imei = ?`,
        [codec, imei]
      );
    },
  };
}
