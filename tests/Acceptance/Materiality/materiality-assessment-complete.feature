Feature:
    I want to complete, reopen and restart the materiality assessment of a carbon footprint and see the relevance of each category

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given standard materiality topic "4" with id "T4" exists
        Given materiality topic "Sin preguntas" with id "T-EMPTY" exists
        Given materiality question "¿Qué volumen de emisiones representa?" with id "Q1" for criterion "magnitude" with scale "level", weight 2, position 1 and topic "T4" exists
        Given materiality question "¿Puedes influir en ella?" with id "Q2" for criterion "influence" with scale "level", weight 1, position 2 and topic "T4" exists
        Given materiality question "¿Supone un riesgo?" with id "Q3" for criterion "risk" with scale "level", weight 1, position 3 and topic "T4" exists
        Given materiality question "¿Interesa a los grupos de interés?" with id "Q4" for criterion "stakeholder_interest" with scale "level", weight 1, position 4 and topic "T4" exists
        Given materiality question "¿Está externalizada?" with id "Q5" for criterion "outsourcing" with scale "yes_no", weight 1, position 5 and topic "T4" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists

    Scenario: Completing with unanswered questions shows an error
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I post to the materiality assessment "A1" action "complete"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "Faltan preguntas por responder."
        Then materiality assessment "A1" should be draft
        Then property carbon footprint "999" should have status "scope_2_answered"

    Scenario: Completing the assessment scores each category and answers the step
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        Given materiality assessment "A1" has value 3 for question "Q1" in topic "T4"
        Given materiality assessment "A1" has value 2 for question "Q2" in topic "T4"
        Given materiality assessment "A1" has value 1 for question "Q3" in topic "T4"
        Given materiality assessment "A1" has value 3 for question "Q4" in topic "T4"
        Given materiality assessment "A1" has value 3 for question "Q5" in topic "T4"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I post to the materiality assessment "A1" action "complete"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "Evaluación de materialidad completada."
        Then I see a text "75,00 %"
        Then I see a text "---"
        Then materiality assessment "A1" should be completed
        Then materiality assessment "A1" should have score "75,00" for topic "T4"
        Then materiality assessment "A1" should have score "---" for topic "T-EMPTY"
        Then property carbon footprint "999" should have status "materiality_answered"
        When I click on link "Siguiente"
        Then the response status should be 200
        Then the current route should be "property_carbon_footprints_scope_3"

    Scenario: Completing the assessment omits the categories below the threshold and pre-excludes their source
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        Given materiality assessment "A1" has all questions answered with value 1
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I post to the materiality assessment "A1" action "complete"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "Umbral"
        Then I see a text "50 %"
        Then I see a text "Omitir"
        Then I see a text "Sin puntuación"
        Then property carbon footprint "999" should have source "upstream_transport" excluded in scope "scope_3" with reason "not_significant"

    Scenario: The relevance threshold of the settings decides the verdict
        Given materiality settings with threshold 10 exist
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        Given materiality assessment "A1" has value 1 for question "Q1" in topic "T4"
        Given materiality assessment "A1" has value 3 for question "Q2" in topic "T4"
        Given materiality assessment "A1" has value 1 for question "Q3" in topic "T4"
        Given materiality assessment "A1" has value 1 for question "Q4" in topic "T4"
        Given materiality assessment "A1" has value 1 for question "Q5" in topic "T4"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I post to the materiality assessment "A1" action "complete"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "16,67 %"
        Then I see a text "10 %"
        Then I see a text "Calcular"
        Then "property" carbon footprint "999" should not have source "upstream_transport" excluded in scope "scope_3"

    Scenario: Reopening the assessment returns it to draft and removes the scores
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        Given materiality assessment "A1" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I post to the materiality assessment "A1" action "reopen"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "Evaluación de materialidad reabierta."
        Then I do not see a text "Relevancia"
        Then materiality assessment "A1" should be draft
        Then materiality assessment "A1" should have score "---" for topic "T4"

    Scenario: Restarting the questionnaire removes the assessment
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I post to the materiality assessment "A1" action "remove"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "Cuestionario reiniciado."
        Then I see a button with name "start_materiality_assessment[start]"
        Then materiality assessment "A1" should be removed

    Scenario: With the footprint completed the assessment cannot be completed
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with employees answered exists
        Given materiality assessment with id "A1" for "property" carbon footprint "998" of account "666" exists
        Given materiality assessment "A1" has all questions answered with value 3
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/998/step/materiality"
        Given property carbon footprint "998" is completed
        When I post to the materiality assessment "A1" action "complete"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "La huella está completada. Reábrela para modificarla."
        Then I do not see a text "Completar evaluación"
        Then materiality assessment "A1" should be draft

    Scenario Outline: With the footprint completed the assessment cannot be reopened nor restarted
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with employees answered exists
        Given materiality assessment with id "A1" for "property" carbon footprint "998" of account "666" exists
        Given materiality assessment "A1" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/998/step/materiality"
        Given property carbon footprint "998" is completed
        When I post to the materiality assessment "A1" action "<action>"
        Then I am redirected to route "property_carbon_footprints_materiality"
        Then I see a text "La huella está completada. Reábrela para modificarla."
        Then I do not see a text "Reabrir evaluación"
        Then I do not see a text "Reiniciar cuestionario"
        Then materiality assessment "A1" should be completed

        Examples:
            | action |
            | reopen |
            | remove |

    Scenario Outline: An owner of another account cannot complete nor restart the assessment
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        Given materiality assessment "A1" has all questions answered with value 3
        Given account "OtherCompany" with id "777" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        When I am logged as "other@dediez.software"
        When I post to the materiality assessment "A1" action "<action>" with a valid token
        Then the response status should be 403
        Then materiality assessment "A1" should be draft

        Examples:
            | action   |
            | complete |
            | remove   |

    Scenario: An owner of another account cannot reopen the assessment
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        Given materiality assessment "A1" is completed
        Given account "OtherCompany" with id "777" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        When I am logged as "other@dediez.software"
        When I post to the materiality assessment "A1" action "reopen" with a valid token
        Then the response status should be 403
        Then materiality assessment "A1" should be completed

    Scenario: Removing the footprint removes its assessment
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/remove" with csrf token "remove"
        Then materiality assessment "A1" should be removed
        Then materiality assessment for "property" carbon footprint "999" should not exist
