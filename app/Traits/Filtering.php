<?php

/**
 * Funciones para gestionar de forma centraliza el filtrado de los modelos cuando se requiere el listado de su  objetos
 * @author Carlos Donis (CD) <cdonisdiaz@gmail.com> 
 * 
 */

namespace App\Traits;

use \Illuminate\Database\Eloquent\Builder;

trait Filtering
{
  /**
   * Scope dinámico para devolver los objetos filtrados según "keyword". Incluye una cláusula where en la consulta de forma tal
   * que sean devueltos aquellos objetos cuyos valores de atributos concatenados contengan el "keyword" recibido como parámetro
   * 
   * @param string $keyword: Cadena por la cual se desea filtrar
   * @param array $filters: Conjunto de atributos que serán incluidos en la búsqueda de la cadena
   * 
   */
  public function scopeWithKeywordSearch(Builder $query, string $keyword = null, array $searchingFields = [])
  {
    $whereRawClause = "?";
    $strParam = "true";
    if ($keyword && count($searchingFields) > 0) {
      $concatenatedFields = implode(", ' ', ", $searchingFields);
      $whereRawClause = "concat({$concatenatedFields}) ~* ?";
      $strParam = $keyword;
    };
    return $query->whereRaw($whereRawClause, [$strParam]);
  }

  /**
   * Scope dinámico para filtrar los objetos de acuerdo a criterios de filtro especificdos.
   * Ej. de uso: $modelo->withFiltering($filters, $fieldsMap) 
   * 
   * @param array $filters: Arreglo asociativo cuyas llaves refieren el nombre del atributo y los valores el filtro a aplicar 
   *                        Los valores pueden ser un arreglo de valores o un único valor.
   *  Ej. 
   *    $filters = [
   *      "provincia" => ["Villa Clara", "Matanzas"],
   *      "funciones" => "Representante"
   *      "fecha_entrega" => ["2021-01-16", "2021-05-01"]
   *    ]
   * 
   * @param array fieldsMap: Mapping de los nombres de los atributos recibidos en el filtro con los nombres de atributos utilizados en la consulta.
   * En la clausula WHERE de una consulta se requiere el uso del nombre del campo y no su posible alias definido en la clausula SELECT debido a 
   * que el gestor de BD ejecuta la clausula WHERE antes de ejecutar la cláusula SELECT y por tanto, el alias no existe en ese momento   
   */
  public function scopeWithFiltering(Builder $query, array $filters = null, array $fieldsMap = [])
  {
    $newQuery = $query;
    if ($filters && \is_array($filters)) {
      foreach ($filters as $field => $filterValue) {
        if ($filterValue) {
          $operator = "~*";
          $fieldRef = $field;
          if (array_key_exists($field, $fieldsMap)) {
            $mapping = $fieldsMap[$field];
            if (\is_array($mapping)) {
              $fieldRef = $mapping[0];
              $operator = (count($mapping) > 1) ? $mapping[1] : $operator;
            } else {
              $fieldRef = $mapping;
            }
          }
          $this->setFieldWhere($newQuery, $fieldRef, $filterValue, $operator);
        }
      }
    }
    return $newQuery;
  }

  protected function setFieldWhere(Builder &$query, string $field, $filterValue, $operator = '~*')
  {
    if (\is_array($filterValue)) {                                        // El filtro es un arreglo
      // Si los dos primeros valores del filtro son de tipo fecha, se asume que el atributo a filtrar es de tipo fecha
      if (
        \DateTime::createFromFormat('d-m-Y H:i:s', $filterValue[0]) &&
        \DateTime::createFromFormat('d-m-Y H:i:s', $filterValue[1])
      ) {     
        // Incluir objeto si el valor del campo está entre las dos fechas especificadas por el filtro
        $filterValue[0] = \DateTime::createFromFormat('d-m-Y H:i:s', $filterValue[0]);
        $filterValue[1] = \DateTime::createFromFormat('d-m-Y H:i:s', $filterValue[1]);
        $query->whereBetween($field, $filterValue);
      } else {                                                            // Se asume que el atributo es de cualquier tipo, excepto fecha
        // Incluir objeto si el valor del campo contiene a alguno de los valores del filtro
        if ($operator === "=") {
          $query->whereIn($field, $filterValue);
        } else {
          // Obtener cadena con un "|" (considerado como OR logico para el operador '~*') entre valores, ej. "valorA|valorB|valorC|valorD"
          $values = implode('|', $filterValue);
          $query->where($field, $operator, "({$values})");
        }
      }
    } else {                                                              // El filtro es un valor simple
      // Si el valor del filtro es de tipo fecha, se asume que el atributo a filtrar es de tipo fecha
      if (\DateTime::createFromFormat('d-m-Y H:i:s', $filterValue)) {           // Se asume que el atributo es de tipo fecha
        // Incluir objeto si el valor del campo coincide con la fecha especificada por el filtro
        $filterValue = \DateTime::createFromFormat('d-m-Y H:i:s', $filterValue);
        $query->whereDate($field, $filterValue);
      } else {                                                            // Se asume que el atributo es de cualquier tipo, excepto fecha
        // Incluir objeto si el valor del campo contiene el valor del filtro
        $query->where($field, $operator, $filterValue);
      }
    }
  }

  /**
   * Adiciona a la consulta, las cláusulas de ordenamiento incluidas en el parámetro "sorters". Se le debe pasar un criterio de 
   * ordenamiento por defecto que será utilizado en caso de que no se especifique ningún criterio mediante "sorters"
   * 
   * Ej. de uso común: 
   *      $modelo->withSorting($defaultSort, $sorters)
   * 
   * Ej. de uso cuando se desean incluir los campos extras en el ordenamiento 
   *      $modelo->withSorting($defaultSort, $sorters, $extrafields, $queryResourceAlias) 
   * 
   * @param array $defaultSort:   Obligatorio. Arreglo asociativo que especifica el criterio de ordenamiento por defecto a utilizar 
   * en la consulta. Las llaves se refieren a los campos del modelo a incluir en el ordenamiento y los valores a la dirección del orden
   * a utilizar: 'ascend' | 'descend'
   * 
   * @param array $sorters:       Opcional. Arreglo asociativo que especifica el criterio de ordenamiento a utilizar 
   * en la consulta. Las llaves se refieren a los campos del modelo a incluir en el ordenamiento y los valores a la dirección del orden
   * a utilizar: 'ascend' | 'descend'  
   * 
   * @param EloquentCollection $extrafields: Opcional. Utilizado cuando se desean considerar los campos extras en el ordenamiento. Contiene
   * la lista de campos extras del modelo en cuestión
   * 
   * @param string $queryResourceAlias: Opcional. Utilizado cuando se desean considerar los campos extras en el ordenamiento. Alias 
   * utilizado en la consulta para referir la tabla del recurso que contiene el campo "extrafields"
   *  
   */
  public function scopeWithSorting(Builder $query, array $defaultSort = [], array $sorters = null, $extrafields = null, $queryResourceAlias = null)
  {
    // Inicializar variable utilizada para conformar las cláusulas sort con el criterio de ordenamiento por defecto 
    $sortCriterias = ($sorters && \is_array($sorters) && count($sorters) > 0) ? $sorters : $defaultSort;
    // Incluir cláusula "orderBy" para cada criterio de ordenamiento
    foreach ($sortCriterias as $field => $direction) {
      if ($direction) {
        $order = ($direction === 'ascend') ? 'asc' : 'desc';
        $_field = $field;

        // Si se trata de un campo personalizado, se debe incluir en la cláusula con la referencia al alias
        $extrafield = (isset($extrafields)) ? $extrafields->where('reference', $field)->first() : null;
        if ($extrafield) {                                                   // El filtro es de un campo extra
          $_field = ($queryResourceAlias) ? "{$queryResourceAlias}.extrafields->{$field}" : "extrafields->{$field}";
        }

        //Crear cláusula orderBY
        $query->orderBy($_field, $order);
      }
    }

    return $query;
  }

  /**
   * Obtiene el nombre de la tabla del modelo de forma estática.
   */
  public static function tableName()
  {
    return with(new static)->getTable();
  }
}
