/**
 * Fixtures do catálogo público de cursos usadas pelos E2E.
 *
 * Estrutura idêntica à resposta real de `GET /api/v1/cursos`:
 * `{ data: [{ id, titulo, modalidades: [{ id, nome }] }] }`.
 *
 * Não representa dados de produção — os IDs e nomes existem apenas
 * dentro do teste, via interceptação de rede.
 */

export type Modalidade = { id: number; nome: string };
export type Curso = { id: number; titulo: string; modalidades: Modalidade[] };

export const cursosFixture: Curso[] = [
    {
        id: 101,
        titulo: 'Curso Médio em Teologia',
        modalidades: [
            { id: 1011, nome: 'EAD' },
            { id: 1012, nome: 'Presencial' },
        ],
    },
    {
        id: 102,
        titulo: 'Habilitação ao Diaconato',
        modalidades: [
            { id: 1021, nome: 'EAD' },
            { id: 1022, nome: 'Presencial' },
        ],
    },
    {
        id: 103,
        titulo: 'Liderança Cristã Ministerial',
        modalidades: [
            { id: 1031, nome: 'EAD' },
            { id: 1032, nome: 'Presencial' },
        ],
    },
];

export const catalogoCursosBody = { data: cursosFixture };
