Feature:
    I want to start the materiality assessment of a carbon footprint choosing the sector of the organization

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given standard materiality topic "4" with id "T4" exists
        Given standard materiality topic "6" with id "T6" exists
        Given materiality question "¿Qué volumen de emisiones representa?" with id "Q1" for criterion "magnitude" with scale "level", weight 2 and position 1 exists
        Given materiality question "¿Puedes influir en ella?" with id "Q2" for criterion "influence" with scale "level", weight 1 and position 2 exists
        Given materiality question "¿Supone un riesgo?" with id "Q3" for criterion "risk" with scale "level", weight 1 and position 3 exists
        Given materiality question "¿Interesa a los grupos de interés?" with id "Q4" for criterion "stakeholder_interest" with scale "level", weight 1 and position 4 exists
        Given materiality question "¿Está externalizada?" with id "Q5" for criterion "outsourcing" with scale "yes_no", weight 1 and position 5 exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists

    Scenario: Starting without sector only asks the questions without sectors
        Given materiality sector "Logística" with id "S-LOG" exists
        Given materiality question "¿Subcontratas la logística?" with id "Q-LOG" for criterion "outsourcing", topic "T4" and sectors "S-LOG" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I start the materiality assessment without sector
        Then I am redirected to route "materiality_assessments_topic"
        Then I see a text "1/2"
        Then I see a text "¿Qué volumen de emisiones representa?"
        Then I do not see a text "¿Subcontratas la logística?"
        Then materiality assessment for "property" carbon footprint "999" should have 5 answers for topic "T4"
        Then materiality assessment for "property" carbon footprint "999" should have 5 answers for topic "T6"

    Scenario Outline: The sector decides which questions are asked
        Given materiality sector "Logística" with id "S-LOG" exists
        Given materiality sector "Hotelero" with id "S-HOT" exists
        Given materiality question "¿Subcontratas la logística?" with id "Q-LOG" for criterion "outsourcing", topic "T4" and sectors "S-LOG" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I start the materiality assessment with sector "<sector>"
        Then I am redirected to route "materiality_assessments_topic"
        Then materiality assessment for "property" carbon footprint "999" should have <transport> answers for topic "T4"
        Then materiality assessment for "property" carbon footprint "999" should have 5 answers for topic "T6"

        Examples:
            | sector | transport |
            | S-LOG  | 6         |
            | S-HOT  | 5         |

    Scenario: Without sectors in the masters the selector is not shown
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Then the response status should be 200
        Then I do not see a form field "start_materiality_assessment[sectorId]"
        Then I see a text "No hay sectores definidos: se aplicarán las preguntas comunes."
        When I start the materiality assessment without sector
        Then I am redirected to route "materiality_assessments_topic"

    Scenario: The completed assessment of the previous year preselects the sector and copies the matching answers
        Given materiality sector "Logística" with id "S-LOG" exists
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with scope 2 answered exists
        Given materiality assessment with id "A-2024" for "property" carbon footprint "998" of account "666" with sector "S-LOG" exists
        Given materiality assessment "A-2024" is completed
        Given materiality question "¿Hay datos primarios?" with id "Q-NEW" for criterion "other" with scale "yes_no", weight 1 and position 6 exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Then I see an option "S-LOG" in form field "start_materiality_assessment[sectorId]" with attribute "selected" "selected"
        Then I see a text "Partir de la evaluación de 2024"
        When I start the materiality assessment copying the previous answers
        Then I am redirected to route "materiality_assessments_topic"
        Then materiality assessment for "property" carbon footprint "999" should have 6 answers for topic "T4"
        Then materiality assessment for "property" carbon footprint "999" should have 10 answered questions

    Scenario: Starting an assessment that already exists goes back to the step
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        When I start the materiality assessment without sector
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "Borrador"

    Scenario: An owner of another account cannot start the assessment
        Given account "OtherCompany" with id "777" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        When I am logged as "other@dediez.software"
        When I post the start of the materiality assessment of "property" carbon footprint "999"
        Then the response status should be 403
        Then materiality assessment for "property" carbon footprint "999" should not exist

    Scenario: The assessment cannot be started before answering scope 2
        Given property carbon footprint with id "997" for property "1111" of account "666" and year "2023" with scope 1 answered exists
        When I am logged as "owner@dediez.software"
        When I post the start of the materiality assessment of "property" carbon footprint "997"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I am redirected to route "property_carbon_footprints_scope_2"
        Then I see a text "Responde el alcance 2 antes de evaluar la materialidad."
        Then materiality assessment for "property" carbon footprint "997" should not exist
        Then property carbon footprint "997" should have status "scope_1_answered"
