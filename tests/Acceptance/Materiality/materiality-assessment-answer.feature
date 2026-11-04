Feature:
    I want to answer the whole materiality questionnaire of a carbon footprint from the materiality step

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given standard materiality topic "4" with id "T4" exists
        Given standard materiality topic "6" with id "T6" exists
        Given materiality question "¿Qué volumen de emisiones representa?" with id "Q1" for criterion "magnitude" with scale "level", weight 2 and position 1 exists
        Given materiality question "¿Está externalizada?" with id "Q5" for criterion "outsourcing" with scale "yes_no", weight 1 and position 5 exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists

    Scenario: Opening a category redirects to the step with the category anchor
        When I am logged as "owner@dediez.software"
        When I go to the materiality assessment "A1" topic "T4"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "Cuestionario"
        Then I see a text "¿Está externalizada?"

    Scenario: Answering a category saves the answers and stays in the step
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I submit the materiality topic form of topic "T4" with value "3" for all questions
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then materiality assessment "A1" should have 2 answered questions
        Then I see a text "2/4"

    Scenario: Answering a category as an ajax request returns the counters
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I submit the materiality topic form of topic "T4" with value "3" for all questions as an ajax request
        Then the response status should be 200
        Then the materiality answer response should report 2 of 2 answered in the topic and 2 of 4 in total
        Then materiality assessment "A1" should have 2 answered questions

    Scenario: A value outside the scale is rejected
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I submit the materiality topic form of topic "T4" with value "2" for all questions
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "No se han podido guardar las respuestas."
        Then materiality assessment "A1" should have 0 answered questions

    Scenario: A value outside the scale is rejected as an ajax request
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I submit the materiality topic form of topic "T4" with value "2" for all questions as an ajax request
        Then the response status should be 422
        Then the materiality answer response should report the message "No se han podido guardar las respuestas."
        Then materiality assessment "A1" should have 0 answered questions

    Scenario: A non numeric value is rejected
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I submit the materiality topic form of topic "T4" with value "3abc" for all questions
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "No se han podido guardar las respuestas."
        Then materiality assessment "A1" should have 0 answered questions

    Scenario: A non numeric value is rejected as an ajax request
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I submit the materiality topic form of topic "T4" with value "3abc" for all questions as an ajax request
        Then the response status should be 422
        Then materiality assessment "A1" should have 0 answered questions

    Scenario: Saving a category with unanswered questions is allowed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I submit the materiality topic form of topic "T4" with value "" for all questions
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "0/4"
        Then materiality assessment "A1" should have 0 answered questions

    Scenario: The assessment keeps the question text after the administrator edits it
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions/edit/Q1"
        When I submit a form with "text:Texto nuevo" and button "Guardar"
        Then materiality question "Q1" should have help "¿Qué volumen de emisiones representa? help" in locale "es"
        Then materiality assessment "A1" should keep question "Q1" text "¿Qué volumen de emisiones representa?"

    Scenario: A completed assessment hides the questionnaire
        Given materiality assessment "A1" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Then the response status should be 200
        Then I see a text "Reabrir evaluación"
        Then I do not see a text "Acordeón"
        Then I do not see a text "Marcar toda la categoría como Bajo/No"

    Scenario: A completed assessment cannot be answered
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Given materiality assessment "A1" is completed
        When I submit the materiality topic form of topic "T4" with value "1" for all questions as an ajax request
        Then the response status should be 409
        Then the materiality answer response should report the message "La evaluación está completada. Reábrela para modificarla."
        Then materiality assessment "A1" should have score "100,00" for topic "T4"

    Scenario: A completed assessment cannot be answered without javascript
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Given materiality assessment "A1" is completed
        When I submit the materiality topic form of topic "T4" with value "1" for all questions
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "La evaluación está completada. Reábrela para modificarla."
        Then materiality assessment "A1" should have score "100,00" for topic "T4"

    Scenario: The answers of a completed footprint cannot be saved
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with employees answered exists
        Given materiality assessment with id "A2" for "property" carbon footprint "998" of account "666" exists
        Given property carbon footprint "998" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/998/step/materiality"
        When I submit the materiality topic form of topic "T4" with value "3" for all questions as an ajax request
        Then the response status should be 409
        Then the materiality answer response should report the message "La huella está completada. Reábrela para modificarla."
        Then materiality assessment "A2" should have 0 answered questions

    Scenario: An owner of another account cannot see the questionnaire
        Given account "OtherCompany" with id "777" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        When I am logged as "other@dediez.software"
        When I go to the materiality assessment "A1" topic "T4"
        Then the response status should be 403
        When I post to the materiality assessment "A1" topic "T4"
        Then the response status should be 403

    Scenario: The questionnaire shows the inherited answers
        Given materiality assessment "A1" is completed
        Given property carbon footprint with id "1000" for property "1111" of account "666" and year "2026" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/1000/step/materiality"
        When I start the materiality assessment copying the previous answers
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "Heredada de la evaluación de 2025"

    Scenario: The matrix lists the generic questions as columns
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Then I see a text "¿Qué volumen de emisiones representa?" in the matrix header
        Then I see a text "¿Está externalizada?" in the matrix header
