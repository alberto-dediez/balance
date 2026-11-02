Feature:
    I want to answer the materiality questionnaire of a carbon footprint one category at a time

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

    Scenario: Answering a category saves the answers and moves to the next one
        When I am logged as "owner@dediez.software"
        When I go to the materiality assessment "A1" topic "T4"
        Then the response status should be 200
        Then I see a text "1/2"
        Then I see a text "¿Está externalizada?"
        When I submit the materiality topic form with value "3" for all questions
        Then I am redirected to route "materiality_assessments_topic"
        Then I see a text "2/2"
        Then materiality assessment "A1" should have 2 answered questions

    Scenario: Answering the last category goes back to the step
        When I am logged as "owner@dediez.software"
        When I go to the materiality assessment "A1" topic "T6"
        When I submit the materiality topic form with value "1" for all questions
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "Progreso"
        Then materiality assessment "A1" should have 2 answered questions

    Scenario: A value outside the scale is rejected
        When I am logged as "owner@dediez.software"
        When I go to the materiality assessment "A1" topic "T4"
        When I submit the materiality topic form with value "2" for all questions
        Then I am redirected to route "materiality_assessments_topic"
        Then I see a text "No se han podido guardar las respuestas."
        Then materiality assessment "A1" should have 0 answered questions

    Scenario: A non numeric value is rejected
        When I am logged as "owner@dediez.software"
        When I go to the materiality assessment "A1" topic "T4"
        When I submit the materiality topic form with value "3abc" for all questions
        Then I am redirected to route "materiality_assessments_topic"
        Then I see a text "No se han podido guardar las respuestas."
        Then materiality assessment "A1" should have 0 answered questions

    Scenario: Saving a category with unanswered questions is allowed
        When I am logged as "owner@dediez.software"
        When I go to the materiality assessment "A1" topic "T4"
        When I submit the materiality topic form with value "" for all questions
        Then I am redirected to route "materiality_assessments_topic"
        Then I see a text "2/2"
        Then materiality assessment "A1" should have 0 answered questions

    Scenario: The assessment keeps the question text after the administrator edits it
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions/edit/Q1"
        When I submit a form with "text:Texto nuevo" and button "Guardar"
        Then materiality question "Q1" should have help "¿Qué volumen de emisiones representa? help" in locale "es"
        Then materiality assessment "A1" should keep question "Q1" text "¿Qué volumen de emisiones representa?"

    Scenario: A completed assessment cannot be answered
        Given materiality assessment "A1" is completed
        When I am logged as "owner@dediez.software"
        When I go to the materiality assessment "A1" topic "T4"
        Then I see a text "Siguiente"
        Then I do not see a text "Guardar y continuar"
        When I submit the materiality topic form with value "1" for all questions
        Then I am redirected to route "materiality_assessments_topic"
        Then I see a text "La evaluación está completada. Reábrela para modificarla."
        Then materiality assessment "A1" should have score "100,00" for topic "T4"

    Scenario: An owner of another account cannot see the questionnaire
        Given account "OtherCompany" with id "777" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        When I am logged as "other@dediez.software"
        When I go to the materiality assessment "A1" topic "T4"
        Then the response status should be 403
