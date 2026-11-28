Feature:
    I want to make sure that the owner of an account edits its contests

    Background:
        Given account "Hoteles Sol" with id "A1" exists
        Given account "A1" has the contests feature enabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "A1" exists
        Given event "Congreso anual" with id "E1" of account "A1" exists

    Scenario: The owner edits a draft contest
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/edit"
        Then the response status should be 200
        Then I see a form field "participationMode"
        Then I see a link to "/es/carbon-footprint/contests/C1/publish"
        When I submit a form with "name:Concurso de invierno" and button "Guardar y salir"
        Then I am redirected to route "contests"
        Then I see a text "Concurso actualizado con éxito"
        Then I see a text "Concurso de invierno"

    Scenario: The owner only changes texts, end, organizer and calculators of a published contest
        Given property "Hotel Mar" with id "P1" of account "A1" exists
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        Given contest "C1" is published
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/edit"
        Then the response status should be 200
        Then I do not see a form field "participationMode"
        Then I do not see a form field "startsAt"
        Then I see a form field "endsAt"
        Then I see a text "Cerrar concurso"
        When I submit a form with "eventIds:[E1]|propertyIds:[P1]" and button "Guardar y salir"
        Then I am redirected to route "contests"
        Then I see a text "Concurso actualizado con éxito"
        When I go to "/es/carbon-footprint/contests/C1/edit"
        Then I see an option "P1" in form field "[propertyIds]" with attribute "selected" "selected"

    Scenario: The owner closes a published contest
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        Given contest "C1" is published
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/close" with csrf token "close"
        Then I am redirected to route "contests_edit"
        Then I see a text "Concurso cerrado con éxito"
        Then I see a text "Resuelto"
        Then I do not see a button with name "contest[save]"

    Scenario: The owner of another account cannot edit the contest
        Given account "Otra" with id "A2" exists
        Given account "A2" has the contests feature enabled
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "A2" exists
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/edit"
        Then the response status should be 403
