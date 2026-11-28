Feature:
    I want to make sure that the owner of an account lists its contests

    Background:
        Given account "Hoteles Sol" with id "A1" exists
        Given account "A1" has the contests feature enabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "A1" exists
        Given event "Congreso anual" with id "E1" of account "A1" exists

    Scenario: The owner sees an empty list
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests"
        Then the response status should be 200
        Then I see a text "Todavía no has creado ningún concurso."
        Then I see a link to "/es/carbon-footprint/contests/register"

    Scenario: The owner only sees the contests of its account
        Given account "Otra" with id "A2" exists
        Given account "A2" has the contests feature enabled
        Given event "Feria" with id "E2" of account "A2" exists
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        Given contest "Concurso ajeno" with id "C2" of account "A2" for "event" "E2" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests"
        Then the response status should be 200
        Then I see a text "Concurso de verano"
        Then I do not see a text "Concurso ajeno"
        Then I see a text "Borrador"
        Then I see a link to "/es/carbon-footprint/contests/C1/edit"
        Then I see a link to "/es/carbon-footprint/contests/C1/entries"

    Scenario: The owner filters by status
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        Given contest "Concurso publicado" with id "C2" of account "A1" for "event" "E1" exists
        Given contest "C2" is published
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests"
        When I filter by "status" with "published"
        Then I see a text "Concurso publicado"
        Then I do not see a text "Concurso de verano"

    Scenario: An account without the contests feature cannot list them
        Given account "Sin concursos" with id "A3" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "A3" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/carbon-footprint/contests"
        Then the response status should be 403

    Scenario: An admin that is not impersonating cannot list them
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/carbon-footprint/contests"
        Then the response status should be 403

    Scenario: A user of the account cannot list them
        Given user "user@dediez.software" with role "ROLE_USER" of account "A1" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/carbon-footprint/contests"
        Then the response status should be 403
