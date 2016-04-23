# Dbf2Csv
_Convert DBF files to CSV_
## Server Requirements
#### How to install dbase on Ubuntu (14.04):
1. install **php5-dev**

  ```sh
  $ sudo apt-get install php5-dev
  ```
2. install dbase via **pecl**

  ```sh
  $ sudo pecl install dbase
  ```
3. load extension
  
  * create **dbase.ini** file in **/etc/php5/mods-available/** directory with this content:

  ```sh
  extension=dbase.so
  ```
	
  * create **sym link** in **/etc/php5/cli/conf.d/** directory:

  ```sh
  $ sudo ln -s ../../mods-available/dbase.ini 20-dbase.ini
  ```

##Usage

```sh
/full/path/to/folder/bin/dbf2csv convert "/path/to/database.dbf" "/path/to/output.csv" <charset database (optional)> <charset output file (optional)>
```


###Install phar

```sh
curl -O http://metadone.ru/dbf2csv.phar
chmod +x dbf2csv.phar
sudo mv dbf2csv.phar /usr/local/bin/dbf2csv
```

###Example
```sh
/var/www/dbf2csv/bin/dbf2csv convert "/home/user/example.DBF" "/home/user/output.csv" CP866 UTF-8
```

Phar example

```sh
dbf2csv convert "/home/user/example.DBF" "/home/user/output.csv" CP866 UTF-8
```