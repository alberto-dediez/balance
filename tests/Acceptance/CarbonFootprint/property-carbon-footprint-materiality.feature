Feature:
    I want the materiality verdicts to pre-exclude or activate the scope 3 categories of a property carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with materiality answered exists
        Given materiality decisions for "property" carbon footprint "999":
            | topicNumber | score | threshold | forced | verdict   |
            | 5           | 38    | 50        | no     | omit      |
            | 1           | 62.5  | 50        | no     | calculate |
            | 2           | 20    | 50        | yes    | calculate |

    Scenario: The scope 3 step shows the materiality verdicts and the pre-excluded categories
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        Then the response status should be 200
        Then I see a text "No material · 38,00 % &lt; 50 %"
        Then I see a text "Material · 62,50 %"
        Then I see a text "Magnitud alta"
        Then I see a text "Análisis de materialidad: relevancia 38,00 % por debajo del umbral 50 %"
        Then "property" carbon footprint "999" should have source "operations_waste" excluded in scope "scope_3" with reason "not_significant"
        Then "property" carbon footprint "999" should not have source "purchased_goods_and_services" excluded in scope "scope_3"

    Scenario: Saving the scope 3 step without changes keeps the recommended exclusions
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form as it is
        Then I am redirected to route "property_carbon_footprints_providers"
        Then "property" carbon footprint "999" should have source "operations_waste" excluded in scope "scope_3" with reason "not_significant"

    Scenario: Including a non material category without justification is rejected
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form including source "operations_waste" with materiality justification ""
        Then I am redirected to route "property_carbon_footprints_scope_3"
        Then I see a text "Indica el motivo por el que anulas la recomendación de materialidad de Residuos generados en las operaciones"
        Then "property" carbon footprint "999" should have source "operations_waste" excluded in scope "scope_3" with reason "not_significant"
        Then property carbon footprint "999" should have status "materiality_answered"

    Scenario: Including a non material category with justification records the override
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form including source "operations_waste" with materiality justification "Tenemos datos de residuos"
        Then I am redirected to route "property_carbon_footprints_providers"
        Then "property" carbon footprint "999" should not have source "operations_waste" excluded in scope "scope_3"
        Then "property" carbon footprint "999" should have a materiality override on source "operations_waste"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        Then I see a text "Anulada por"
        Then I see a text "Tenemos datos de residuos"

    Scenario: The override justification is shown escaped in the events log
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form including source "operations_waste" with materiality justification "<b>x</b>"
        Then I am redirected to route "property_carbon_footprints_providers"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/events"
        Then the response status should be 200
        Then I see a text "&lt;b&gt;x&lt;/b&gt;"
        Then I do not see a text "<b>x</b>"

    Scenario: Excluding a material category with justification records the override
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope 3 step form excluding source "purchased_goods_and_services" with reason "out_of_scope" and materiality justification "Fuera del perímetro"
        Then I am redirected to route "property_carbon_footprints_providers"
        Then "property" carbon footprint "999" should have source "purchased_goods_and_services" excluded in scope "scope_3" with reason "out_of_scope"
        Then "property" carbon footprint "999" should have a materiality override on source "purchased_goods_and_services"

    Scenario: The results show the origin of each exclusion
        Given property carbon footprint with id "888" for property "1111" of account "666" and year "2024" with employees answered exists
        Given materiality decisions for "property" carbon footprint "888":
            | topicNumber | score | threshold | forced | verdict   |
            | 5           | 38    | 50        | no     | omit      |
            | 1           | 62.5  | 50        | no     | calculate |
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/888/step/scope-3"
        When I submit the scope 3 step form excluding source "purchased_goods_and_services" with reason "out_of_scope" and materiality justification "Fuera del perímetro"
        When I go to "/es/property-carbon-footprints/888/step/results"
        Then the response status should be 200
        Then I see a text "Origen"
        Then I see a text "Recomendación de materialidad"
        Then I see a text "Anulación de la recomendación"

    Scenario: A category with activities that becomes non material stays pending of review
        Given property carbon footprint with id "888" for property "1111" of account "666" and year "2024" with employees answered exists
        Given "property" carbon footprint "888" has a purchase service activity "PS1" in scope 3 for source "operations_waste"
        Given materiality decisions for "property" carbon footprint "888":
            | topicNumber | score | threshold | forced | verdict |
            | 5           | 38    | 50        | no     | omit    |
            | 4           | 30    | 50        | no     | omit    |
        When I am logged as "owner@dediez.software"
        Then "property" carbon footprint "888" should have source "upstream_transport" excluded in scope "scope_3" with reason "not_significant"
        Then "property" carbon footprint "888" should not have source "operations_waste" excluded in scope "scope_3"
        When I go to "/es/property-carbon-footprints/888/step/scope-3"
        Then I see a text "El análisis de materialidad ha cambiado"
        When I go to "/es/property-carbon-footprints/888/step/results"
        Then I see a text "pendientes de revisión"

    Scenario: The materiality step summarises the decision of each category
        Given standard materiality topic "5" with id "T5" exists
        Given standard materiality topic "14" with id "T14" exists
        Given materiality topic "Eventos corporativos" with id "T-OWN" in scope "scope_3" exists
        Given materiality question "¿Qué volumen de residuos genera?" with id "Q5" for criterion "influence" and topic "T5" exists
        Given materiality question "¿Tiene franquicias?" with id "Q14" for criterion "influence" and topic "T14" exists
        Given materiality question "¿Organiza eventos?" with id "QOWN" for criterion "influence" and topic "T-OWN" exists
        Given property carbon footprint with id "777" for property "1111" of account "666" and year "2024" with scope 2 answered exists
        Given materiality assessment with id "A1" for "property" carbon footprint "777" of account "666" exists
        Given materiality assessment "A1" has all questions answered with value 1
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/777/step/materiality"
        When I post to the materiality assessment "A1" action "complete"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "Umbral"
        Then I see a text "Veredicto"
        Then I see a text "Estado en la huella"
        Then I see a text "Omitir"
        Then I see a text "Excluida (recomendación)"
        Then I see a text "Sin fuente de cálculo"
        Then I see a text "No aplica a esta huella"
        Then I see a text "Sin evaluar"
        Then "property" carbon footprint "777" should have source "operations_waste" excluded in scope "scope_3" with reason "not_significant"
        Then "property" carbon footprint "777" should not have source "franchises" excluded in scope "scope_3"

    Scenario: Restarting the questionnaire withdraws the recommended exclusions
        Given standard materiality topic "5" with id "T5" exists
        Given materiality question "¿Qué volumen de residuos genera?" with id "Q5" for criterion "influence" and topic "T5" exists
        Given property carbon footprint with id "777" for property "1111" of account "666" and year "2024" with scope 2 answered exists
        Given materiality assessment with id "A1" for "property" carbon footprint "777" of account "666" exists
        Given materiality assessment "A1" has all questions answered with value 1
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/777/step/materiality"
        When I post to the materiality assessment "A1" action "complete"
        Then "property" carbon footprint "777" should have source "operations_waste" excluded in scope "scope_3" with reason "not_significant"
        When I go to "/es/property-carbon-footprints/777/step/materiality"
        When I post to the materiality assessment "A1" action "remove"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then "property" carbon footprint "777" should not have source "operations_waste" excluded in scope "scope_3"
        Then "property" carbon footprint "777" should have 0 materiality decisions

    Scenario: The materiality step warns about the categories pending of review
        Given standard materiality topic "5" with id "T5" exists
        Given materiality question "¿Qué volumen de residuos genera?" with id "Q5" for criterion "influence" and topic "T5" exists
        Given property carbon footprint with id "888" for property "1111" of account "666" and year "2024" with employees answered exists
        Given "property" carbon footprint "888" has a purchase service activity "PS1" in scope 3 for source "operations_waste"
        Given materiality assessment with id "A1" for "property" carbon footprint "888" of account "666" exists
        Given materiality assessment "A1" has all questions answered with value 1
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/888/step/materiality"
        When I post to the materiality assessment "A1" action "complete"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "pendientes de revisión"
        Then I see a text "Pendiente de revisión"
        Then "property" carbon footprint "888" should not have source "operations_waste" excluded in scope "scope_3"

    Scenario: Reopening the assessment keeps showing the last decision applied to the footprint
        Given standard materiality topic "5" with id "T5" exists
        Given materiality question "¿Qué volumen de residuos genera?" with id "Q5" for criterion "influence" and topic "T5" exists
        Given property carbon footprint with id "777" for property "1111" of account "666" and year "2024" with scope 2 answered exists
        Given materiality assessment with id "A1" for "property" carbon footprint "777" of account "666" exists
        Given materiality assessment "A1" has all questions answered with value 1
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/777/step/materiality"
        When I post to the materiality assessment "A1" action "complete"
        When I go to "/es/property-carbon-footprints/777/step/materiality"
        When I post to the materiality assessment "A1" action "reopen"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "Completar evaluación"
        Then I see a text "Estado en la huella"
        Then I see a text "Excluida (recomendación)"
        Then "property" carbon footprint "777" should have source "operations_waste" excluded in scope "scope_3" with reason "not_significant"
