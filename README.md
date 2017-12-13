# sORM
Very simple ORM Library
It helps store objects in database with references between them (this time in MySQL only). 

For reference see example folder where described 3 models with references:
- User
- User publication
- Comment to publication

ORM helps to load objects from DB, save changes, delete records by condition or select records by condition with sorting and limiting.
Lib supports "one to many" relation and can, for example, get all comments for publication in given example.
