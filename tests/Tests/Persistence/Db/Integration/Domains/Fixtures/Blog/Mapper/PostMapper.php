<?php

namespace Dms\Core\Tests\Persistence\Db\Integration\Domains\Fixtures\Blog\Mapper;

use Dms\Core\Persistence\Db\Mapping\Definition\MapperDefinition;
use Dms\Core\Persistence\Db\Mapping\EntityMapper;
use Dms\Core\Tests\Persistence\Db\Integration\Domains\Fixtures\Blog\Comment;
use Dms\Core\Tests\Persistence\Db\Integration\Domains\Fixtures\Blog\Post;
use Dms\Core\Tests\Persistence\Db\Integration\Domains\Fixtures\Blog\User;

/**
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class PostMapper extends EntityMapper
{
    /**
     * Defines the entity mapper
     *
     * @param MapperDefinition $map
     *
     * @return void
     */
    protected function define(MapperDefinition $map)
    {
        $map->type(Post::class);
        $map->toTable('posts');

        $map->idToPrimaryKey('id');
        $map->column('author_id')->asInt();

        $map->property('content')->to('content')->asText();

        $map->relation('authorId')
                ->to(User::class)
                ->manyToOneId()
                ->withBidirectionalRelation('postIds')
                ->withRelatedIdAs('author_id');

        $map->relation('comments')
                ->to(Comment::class)
                ->toMany()
                ->identifying()
                ->withParentIdAs('post_id');
    }
}