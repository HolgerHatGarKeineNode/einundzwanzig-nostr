SELECT 'CREATE DATABASE testing'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'testing')\gexec

SELECT 'CREATE DATABASE einundzwanzig'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'einundzwanzig')\gexec
