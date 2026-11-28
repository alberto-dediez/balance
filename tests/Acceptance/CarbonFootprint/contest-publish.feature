Feature:
    I want to make sure that the owner of an account publishes its contests

    Background:
        Given account "Hoteles Sol" with id "A1" exists
        Given account "A1" has the contests feature enabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "A1" exists
        Given event "Congreso anual" with id "E1" of account "A1" exists

    Scenario: The owner publishes a contest accepting the declaration
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/publish"
        Then the response status should be 200
        Then I see a text "Congreso anual"
        When I tick the checkbox "publish_contest[responsibilityAccepted]"
        When I submit a form with "" and button "Publicar"
        Then I am redirected to route "contests_edit"
        Then I see a text "Concurso publicado con éxito"
        Then I see a text "Publicado"

    Scenario: Without the declaration the contest is not published
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/publish"
        When I submit a form with "" and button "Publicar"
        Then the response status should be 200
        Then I see a text "Debes aceptar la declaración de responsabilidad para publicar el concurso."
        When I go to "/es/carbon-footprint/contests"
        Then I see a text "Borrador"

    Scenario: A calculator cannot be in two published contests
        Given contest "Concurso de primavera" with id "C0" of account "A1" for "event" "E1" exists
        Given contest "C0" is published
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/publish"
        When I tick the checkbox "publish_contest[responsibilityAccepted]"
        When I submit a form with "" and button "Publicar"
        Then the response status should be 200
        Then I see a text "La calculadora «Congreso anual» ya tiene otro concurso publicado."
