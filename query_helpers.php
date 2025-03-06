<?php

// Funzione per generare la WHERE clause
function generateWhereClause($resArray, $whrClause = "") {
    if (!isset($resArray['filters']) || count($resArray['filters']) === 0) {
        return $whrClause;
    }

    $filterMode = $resArray["filter_mode"] ?? "AND";
    $whrClause = $whrClause ?: " WHERE ";
    $andClause = $andClause ?: " AND ";
    $whrClause .= " (";

    $filterGroups = array_map(function ($filterGroup) {
        $curFilterMode = $filterGroup["filter_mode"] ?? "AND";
        $filterConditions = array_map(function ($curFilterField) {
            $field = $curFilterField["field"];
            $type = strtolower($curFilterField["type"]); 
            $value = addslashes($curFilterField["value"]); // Sicurezza contro SQL Injection

            $sqlOperators = [
                "eq"   => "=",
                "neq"  => "<>",
                "lt"   => "<",
                "gt"   => ">",
                "le"   => "<=",
                "ge"   => ">=",
                "like" => "LIKE"
            ];

            // Se il tipo è definito negli operatori, usa il valore corrispondente, altrimenti usa il tipo stesso
            $sqlOperator = $sqlOperators[$type] ?? $type;

            // Gestione speciale per LIKE con controllo sui %
            if ($sqlOperator === "LIKE") {
                $value = (strpos($value, '%') !== false) ? strtoupper($value) : '%' . strtoupper($value) . '%';
                return "(UPPER($field) LIKE '$value')";
            }

            return "($field $sqlOperator '$value')";
        }, $filterGroup["fields"]);

        return " (" . implode(" $curFilterMode ", array_filter($filterConditions)) . ") ";
    }, $resArray['filters']);

    $whrClause .= implode(" $filterMode ", array_filter($filterGroups)) . " ) ";
    return $whrClause;
}

// Funzione per generare la ORDER BY clause
function generateOrderByClause($resArray) {
    if (!isset($resArray['ordby'])) {
        return "";
    }

    $arrOrdby = $resArray['ordby'];
    $orderClauses = [];

    if (isset($arrOrdby[0])) {
        foreach ($arrOrdby as $order) {
            $orderClauses[] = $order["field"] . " " . $order["dir"];
        }
    } else {
        $fields = explode(",", $arrOrdby['field']);
        foreach ($fields as $field) {
            $orderClauses[] = trim($field) . " " . $arrOrdby['dir'];
        }
    }

    return " ORDER BY " . implode(", ", $orderClauses);
}

// Funzione per generare la LIMIT clause
function generateLimitClause($resArray) {
    return isset($resArray['limit']) ? " LIMIT " . (int)$resArray['limit'] : "";
}

?>