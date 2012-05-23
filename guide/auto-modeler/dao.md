# DAO Classes

DAO (Data Access Object) classes are used to interface in a Create/Read/Update fashion with a backend data store.

These data stores can be a database, xml file, csv file, RESTful API backend, or anything else. All you need is a DAO interface class to implement the backend.

## Writing DAO classes

If you write your own DAO class, it should follow the following conventions:

 - It should have a `factory()` method for making new instances with different parameters easily.
 - It should have the following methods:
   - `create()`, which adds a record to the data store
   - `update()`, which persists changes to a record in the data store
   - `delete()`, which removes a record in the data store
 - Any method that changes data in the data store should take a `AutoModeler_Model` type object as the first parameter.
   - Any other parameters to these methods should be optional.


