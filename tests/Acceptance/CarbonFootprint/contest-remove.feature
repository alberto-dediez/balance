Feature:
    I want to make sure that the owner of an account removes draft contests

    Background:
        Given account "Hoteles Sol" with id "A1" exists
        Given account "A1" has the contests feature enabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "A1" exists
        Given event "Congreso anual" with id "E1" of account "A1" exists

    Scenario: The owner removes a draft contest
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/remove" with csrf token "remove"
        Then I am redirected to route "contests"
        Then I see a text "Concurso eliminado con éxito"
        Then I do not see a text "Concurso de verano"

    Scenario: A published contest cannot be removed
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        Given contest "C1" is published
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests"
        Then I do not see a link with title "Eliminar"
        When I go to "/es/carbon-footprint/contests/C1/remove" with csrf token "remove"
        When I follow redirect
        Then I see a text "No se ha podido eliminar el concurso"
        Then I see a text "Concurso de verano"

    Scenario: The remove action requires the csrf token
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/remove"
        Then the response status should be 403
